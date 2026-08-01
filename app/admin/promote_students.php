<?php
require_once __DIR__ . '/../../env/session.php';
require_once __DIR__ . '/../../env/csrf.php';
include __DIR__ . '/../../env/config.php';

require __DIR__ . '/../../vendor/autoload.php'; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

$message = '';
$error = '';
$download_link = '';

$batches = $conn->query("SELECT * FROM batches ORDER BY name");
$classes = $conn->query("SELECT * FROM classes ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ams_csrf_verify_post();
    $action = $_POST['action'] ?? 'promote';
    $old_batch_id = intval($_POST['old_batch_id']);
    $old_class_id = intval($_POST['old_class_id']);
    $new_batch_id = intval($_POST['new_batch_id']);
    $new_class_id = intval($_POST['new_class_id']);

    $old_batch_name = $conn->query("SELECT name FROM batches WHERE id = $old_batch_id")->fetch_assoc()['name'] ?? '';
    $old_class_name = $conn->query("SELECT name FROM classes WHERE id = $old_class_id")->fetch_assoc()['name'] ?? '';
    $new_batch_name = $conn->query("SELECT name FROM batches WHERE id = $new_batch_id")->fetch_assoc()['name'] ?? '';
    $new_class_name = $conn->query("SELECT name FROM classes WHERE id = $new_class_id")->fetch_assoc()['name'] ?? '';

    if ($old_batch_name && $old_class_name && $new_batch_name && $new_class_name) {
        $old_table = "Student_" . preg_replace('/\s+/', '', $old_batch_name) . "_" . preg_replace('/\s+/', '', $old_class_name);
        $new_table = "Student_" . preg_replace('/\s+/', '', $new_batch_name) . "_" . preg_replace('/\s+/', '', $new_class_name);

        $check_old = $conn->query("SHOW TABLES LIKE '$old_table'");
        if ($check_old->num_rows == 0) {
            $error = "❌ Source student table does not exist.";
        } else {
            $check_new = $conn->query("SHOW TABLES LIKE '$new_table'");
            if ($check_new->num_rows == 0) {
                $create_sql = "CREATE TABLE `$new_table` LIKE `$old_table`";
                if (!$conn->query($create_sql)) {
                    $error = "❌ Failed to create new table: " . $conn->error;
                }
            }

            if (!$error) {
                $check_existing = $conn->query("SELECT COUNT(*) as total FROM `$new_table`");
                $existing_count = $check_existing->fetch_assoc()['total'];
                
                // For promote action, error if target has students
                // For copy action, allow adding to existing students
                if ($action === 'promote' && $existing_count > 0) {
                    $error = "❌ This class has already been promoted to the selected target class and batch.";
                } else {
                    $students = $conn->query("SELECT * FROM `$old_table`");
                    $spreadsheet = new Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();
                    $header_written = false;
                    $row_index = 1;
                    $copied = 0;

                    while ($stu = $students->fetch_assoc()) {
                        $stu['batch_id'] = $new_batch_id;
                        $stu['class_id'] = $new_class_id;
                        unset($stu['id']);
                        $fields = array_keys($stu);
                        $fields_list = '`' . implode('`,`', $fields) . '`';
                        $placeholders = rtrim(str_repeat('?,', count($fields)), ',');
                        $stmt = $conn->prepare("INSERT INTO `$new_table` ($fields_list) VALUES ($placeholders)");
                        $types = str_repeat('s', count($fields));
                        $values = array_values($stu);
                        $stmt->bind_param($types, ...$values);

                        if ($stmt->execute()) {
                            $copied++;

                            if (!$header_written) {
                                $col = 'A';
                                foreach ($fields as $field) {
                                    $sheet->setCellValue($col . $row_index, $field);
                                    $col++;
                                }
                                $header_written = true;
                                $row_index++;
                            }

                            $col = 'A';
                            foreach ($values as $value) {
                                $sheet->setCellValue($col . $row_index, $value);
                                $col++;
                            }
                            $row_index++;
                        }
                        $stmt->close();
                    }

                    ams_upload_dir('excel');

                    $file_base_name = "Student_" . preg_replace('/\s+/', '', $new_batch_name) . "_" . preg_replace('/\s+/', '', $new_class_name) . ".xlsx";
                    $file_path = ams_upload_dir('excel') . $file_base_name;

                    $writer = new Xlsx($spreadsheet);
                    $writer->save($file_path);

                    $download_link = ams_upload_url('excel', $file_base_name);
                    if ($action === 'copy') {
                        $message = "Successfully copied <strong>$copied</strong> students from <strong>$old_batch_name, $old_class_name</strong> to <strong>$new_batch_name, $new_class_name</strong>. Total students now: " . ($existing_count + $copied);
                    } else {
                        $message = "Successfully promoted <strong>$copied</strong> students from <strong>$old_batch_name, $old_class_name</strong> to <strong>$new_batch_name, $new_class_name</strong>.";
                    }
                }
            }
        }
    } else {
        $error = "Invalid batch or class selection.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Promote Students</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/promote_students.css">
</head>

<body>
    <div class="container">
        <h2>Promote Students</h2>

        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($download_link): ?>
            <div class="download-link">
                <a href="<?= $download_link ?>" download>Download Excel File</a>
            </div>
        <?php endif; ?>

        <form method="post">
            <?= ams_csrf_field() ?>
            <input type="hidden" name="action" id="action_type" value="promote">
            <select name="old_batch_id" required style="width: 100%; ">
                <option value="">From Batch</option>
                <?php foreach ($batches as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="old_class_id" required style="width: 100%; ">
                <option value="">From Class</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="new_batch_id" required style="width: 100%; ">
                <option value="">To Batch</option>
                <?php foreach ($batches as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="new_class_id" required style="width: 100%; ">
                <option value="">To Class</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="button-group" style="width: 100%; ">
                <button type="submit" onclick="document.getElementById('action_type').value='promote'">Promote Students</button>
            </div>
        </form>
    </div>
</body>

</html>











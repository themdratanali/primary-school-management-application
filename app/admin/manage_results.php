<?php
require_once __DIR__ . '/../../env/session.php';
include '../../env/config.php';

// Admin access only
if (!isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

$conn->set_charset("utf8mb4");

$batches = $conn->query("SELECT id, name FROM batches ORDER BY name");
$classes = $conn->query("SELECT id, name FROM classes ORDER BY name");

$message = '';
$selected_batch_id = '';
$selected_class_id = '';
$selected_student_id = '';
$batch_year = '';
$result_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_year'], $_POST['student_id'], $_POST['class_id'], $_POST['marks'], $_POST['result_type'])) {
    $batch_year = $_POST['batch_year'];
    $selected_batch_id = $_POST['batch_id'] ?? '';
    $selected_class_id = intval($_POST['class_id']);
    $selected_student_id = intval($_POST['student_id']);
    $marksArr = $_POST['marks'];
    $result_type = $_POST['result_type'];

    if (!preg_match('/^\d{4}$/', $batch_year)) {
        die('Invalid batch year.');
    }

    $class_name_res = $conn->query("SELECT name FROM classes WHERE id = $selected_class_id");
    if ($class_name_res && ($row = $class_name_res->fetch_assoc()) && !empty($row['name'])) {
        $class_name = strtolower(str_replace(' ', '_', $row['name']));
    } else {
        die('Class not found.');
    }

    $result_type_clean = strtolower(str_replace(' ', '_', $result_type));
    $table_name = "results_{$batch_year}_{$class_name}_{$result_type_clean}";

    $table_exists_res = $conn->query("SHOW TABLES LIKE '$table_name'");
    $table_exists = $table_exists_res && $table_exists_res->num_rows > 0;

    if (!$table_exists) {
        $create_table_sql = "CREATE TABLE `$table_name` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` INT NOT NULL,
            `class_id` INT NOT NULL,
            `subject_id` INT NOT NULL,
            `marks` INT NOT NULL,
            `exam_type` VARCHAR(50) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        if (!$conn->query($create_table_sql)) {
            die("Error creating table: " . $conn->error);
        }
    }

    $delete_stmt = $conn->prepare("DELETE FROM `$table_name` WHERE student_id = ? AND class_id = ? AND exam_type = ?");
    $delete_stmt->bind_param("iis", $selected_student_id, $selected_class_id, $result_type);
    $delete_stmt->execute();
    $delete_stmt->close();

    $stmt = $conn->prepare("INSERT INTO `$table_name` (student_id, class_id, subject_id, marks, exam_type) VALUES (?, ?, ?, ?, ?)");
    $errors = [];
    foreach ($marksArr as $subject_id => $mark) {
        $subject_id = intval($subject_id);
        $mark = intval($mark);
        if (!$stmt->bind_param("iiiis", $selected_student_id, $selected_class_id, $subject_id, $mark, $result_type)) {
            $errors[] = "Bind param failed: " . $stmt->error;
            continue;
        }
        if (!$stmt->execute()) {
            $errors[] = "Insert failed: " . $stmt->error;
        }
    }
    $stmt->close();

    if (count($errors) === 0) {
        $subjectsResult = $conn->query("SELECT id, name FROM subjects WHERE class_id = $selected_class_id ORDER BY id ASC");
        $subjectNames = [];
        $subjectIds = [];
        while ($row = $subjectsResult->fetch_assoc()) {
            $subjectIds[] = $row['id'];
            $subjectNames[] = $row['name'];
        }

        $student_roll_res = $conn->query("SELECT roll FROM students WHERE id = $selected_student_id");
        $student_roll = ($student_roll_res && ($roll_row = $student_roll_res->fetch_assoc()) && !empty($roll_row['roll'])) ? $roll_row['roll'] : 'N/A';

        $rowData = [$student_roll, $class_name];
        foreach ($subjectIds as $sid) {
            $rowData[] = $marksArr[$sid] ?? '';
        }
        $rowData[] = $result_type;
        $rowData[] = date('Y-m-d H:i:s');

        $excelFolder = ams_upload_dir('excel');
        if (!is_dir($excelFolder)) {
            mkdir($excelFolder, 0777, true);
        }
        $csvFile = $excelFolder . $table_name . '.csv';

        // Write results to CSV
        $handle = fopen($csvFile, 'a');
        if ($handle === false) {
            $message = "Failed to open CSV file for writing.";
        } else {
            // If file is empty, write headers
            if (filesize($csvFile) === 0) {
                $headers = ['Student Roll', 'Class Name'];
                foreach ($subjectNames as $subjName) {
                    $headers[] = $subjName;
                }
                $headers[] = 'Exam Type';
                $headers[] = 'Created At';
                fputcsv($handle, $headers);
            }
            fputcsv($handle, $rowData);
            fclose($handle);
            $message = "Results saved and exported to CSV successfully.";
        }
    } else {
        $message = "Some errors occurred:<br>" . implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Results</title>
    <link rel="shortcut icon" type="image/jpg" href="<?php echo BASE_URL; ?>/uploads/images/এ্যাপেক্স মডেল স্কুল.png"/>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/manage_results.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/fontawesome/css/all.min.css">
</head>

<body>
    <div>
        <form method="post">
            <div class="results-layout">
                <!-- Left Section: Selection -->
                <div class="left">
                    <h3>Student Selection</h3>
                    
                    <?php if ($message): ?>
                        <div class="message <?= strpos($message, 'error') !== false ? 'error' : '' ?>">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="batch_id">Batch:</label>
                        <select name="batch_id" id="batch_id" required>
                            <option value="">Select Batch</option>
                            <?php while ($b = $batches->fetch_assoc()): ?>
                                <option value="<?= $b['id'] ?>" <?= $b['id'] == $selected_batch_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="class_id">Class:</label>
                        <select name="class_id" id="class_id" required>
                            <option value="">Select Class</option>
                            <?php $classes->data_seek(0);
                            while ($c = $classes->fetch_assoc()): ?>
                                <option value="<?= $c['id'] ?>" <?= $c['id'] == $selected_class_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="student_id">Student:</label>
                        <select name="student_id" id="student_id" required>
                            <option value="">Select Student</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="result_type">Result Type:</label>
                        <select name="result_type" id="result_type" required>
                            <option value="">Select Exam Type</option>
                            <option value="1st Tutorial" <?= $result_type == '1st Tutorial' ? 'selected' : '' ?>>1st Tutorial</option>
                            <option value="2nd Tutorial" <?= $result_type == '2nd Tutorial' ? 'selected' : '' ?>>2nd Tutorial</option>
                            <option value="3rd Tutorial" <?= $result_type == '3rd Tutorial' ? 'selected' : '' ?>>3rd Tutorial</option>
                            <option value="1st Term Exam" <?= $result_type == '1st Term Exam' ? 'selected' : '' ?>>1st Term Exam</option>
                            <option value="2nd Term Exam" <?= $result_type == '2nd Term Exam' ? 'selected' : '' ?>>2nd Term Exam</option>
                            <option value="Annual Exam" <?= $result_type == 'Annual Exam' ? 'selected' : '' ?>>Annual Exam</option>
                        </select>
                    </div>

                    <button type="submit" id="submitBtn" class="submit-btn">
                        <i></i> Submit Results
                    </button>

                    <input type="hidden" name="batch_year" id="batch_year" value="<?= htmlspecialchars($batch_year) ?>">
                </div>

                <!-- Right Section: Marks Entry -->
                <div class="right">
                    <h3>Enter Marks</h3>
                    <div id="subjectTableArea">
                        <p style="color: #666; text-align: center; padding: 40px 20px; background: #f9f9f9; border-radius: 8px;">
                            <i class="fas fa-info-circle"></i> Select Batch, Class, Student, and Result Type to enter marks
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            function loadStudents(batchId, classId) {
                if (!batchId || !classId) {
                    $('#student_id').html('<option value="">Select Student</option>');
                    return;
                }
                $.post('<?php echo ams_student_url('get_students_by_batch_class'); ?>', {
                    batch_id: batchId,
                    class_id: classId
                }, function(data) {
                    $('#student_id').html(data);
                });
            }

            function loadSubjects(classId, studentId, batchYear, resultType) {
                if (!classId || !studentId || !batchYear || !resultType) {
                    $('#subjectTableArea').html('<p style="color: #666; text-align: center; padding: 40px 20px; background: #f9f9f9; border-radius: 8px;"><i class="fas fa-info-circle"></i> Select all options to enter marks</p>');
                    $('#submitBtn').hide();
                    return;
                }
                $.post('get_subjects_for_class', {
                    class_id: classId,
                    student_id: studentId,
                    batch_year: batchYear,
                    result_type: resultType
                }, function(data) {
                    // Check if response contains error message
                    if (data.indexOf('Please select') !== -1 || data.indexOf('Invalid') !== -1 || data.indexOf('No subjects') !== -1) {
                        $('#subjectTableArea').html('<p style="color: #666; text-align: center; padding: 40px 20px; background: #f9f9f9; border-radius: 8px;"><i class="fas fa-info-circle"></i> ' + data + '</p>');
                    } else {
                        $('#subjectTableArea').html(data);
                        $('#submitBtn').show();
                    }
                });
            }

            $('#batch_id, #class_id').on('change', function() {
                let batchId = $('#batch_id').val();
                let classId = $('#class_id').val();
                $('#student_id').html('<option value="">Select Student</option>');
                $('#subjectTableArea').html('<p style="color: #666; text-align: center; padding: 40px 20px; background: #f9f9f9; border-radius: 8px;"><i class="fas fa-info-circle"></i> Select all options to enter marks</p>');
                $('#submitBtn').hide();
                if (batchId && classId) {
                    $.post('get_batch_year', {
                        batch_id: batchId
                    }, function(data) {
                        if (data && data.year) {
                            $('#batch_year').val(data.year);
                            loadStudents(batchId, classId);
                        }
                    }, 'json');
                }
            });

$('#student_id, #result_type').on('change', function() {
                let batchYear = $('#batch_year').val();
                let classId = $('#class_id').val();
                let studentId = $('#student_id').val();
                let resultType = $('#result_type').val();
                loadSubjects(classId, studentId, batchYear, resultType);
            });
        });
    </script>
</body>
</html>











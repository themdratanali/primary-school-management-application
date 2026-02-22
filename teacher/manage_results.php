<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['teacher_id']) && !isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$conn->set_charset("utf8mb4");

// Check if user is admin or teacher
$is_admin = isset($_SESSION['admin']);
$teacher_id = $_SESSION['teacher_id'] ?? $_SESSION['admin_id'] ?? 0;
$teacher_name = '';

if (isset($_SESSION['teacher_name'])) {
    $teacher_name = $_SESSION['teacher_name'];
} elseif (isset($_SESSION['admin_name'])) {
    $teacher_name = $_SESSION['admin_name'] . ' (Admin)';
} else {
    $stmt = $conn->prepare("SELECT name FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $teacher_name = $row['name'];
    }
    $stmt->close();
}

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

    // Get class name
    $class_name_res = $conn->query("SELECT name FROM classes WHERE id = $selected_class_id");
    if ($class_name_res && ($row = $class_name_res->fetch_assoc()) && !empty($row['name'])) {
        $class_name = strtolower(str_replace(' ', '_', $row['name']));
    } else {
        die('Class not found.');
    }

    $result_type_clean = strtolower(str_replace(' ', '_', $result_type));
    $table_name = "results_{$batch_year}_{$class_name}_{$result_type_clean}";

    // Validate that teacher only submits marks for their assigned subjects
    if (!$is_admin) {
        foreach ($marksArr as $subject_id => $mark) {
            $subject_id = intval($subject_id);
            $check_stmt = $conn->prepare("
                SELECT ts.id FROM teacher_subjects ts
                INNER JOIN subjects s ON ts.subject_id = s.id
                WHERE ts.teacher_id = ? AND ts.subject_id = ? AND s.class_id = ?
            ");
            $check_stmt->bind_param("iii", $teacher_id, $subject_id, $selected_class_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows === 0) {
                die('You are not authorized to submit marks for this subject.');
            }
            $check_stmt->close();
        }
    }

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
        // Get subject names for notification
        $subjectIds = array_keys($marksArr);
        $subjectIdsStr = implode(',', array_map('intval', $subjectIds));
        $subjectsResult = $conn->query("SELECT id, name FROM subjects WHERE id IN ($subjectIdsStr) ORDER BY id ASC");
        $subjectNames = [];
        while ($row = $subjectsResult->fetch_assoc()) {
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

        $excelFolder = '../uploads/excel/';
        if (!is_dir($excelFolder)) {
            mkdir($excelFolder, 0777, true);
        }
        $csvFile = $excelFolder . $table_name . '.csv';

        $handle = fopen($csvFile, 'a');
        if ($handle === false) {
            $message = "Failed to open CSV file for writing.";
        } else {
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
        
        $subject_names_str = implode(', ', $subjectNames);
        $notification_msg = "Teacher $teacher_name added results for student (Roll: $student_roll) in $class_name for subjects: $subject_names_str";
        $notify_stmt = $conn->prepare("INSERT INTO notifications (type, title, message, created_by) VALUES ('result_added', 'Results Added', ?, ?)");
        $notify_stmt->bind_param("si", $notification_msg, $teacher_id);
        $notify_stmt->execute();
        $notify_stmt->close();
    } else {
        $message = "Some errors occurred:<br>" . implode("<br>", $errors);
    }
}
?>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f5f7fa;
        padding: 15px;
        margin: 0;
    }
    .manage-results-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    .page-title {
        margin-bottom: 20px;
        color: #333;
        font-weight: 700;
        font-size: 20px;
    }
    .page-title i {
        color: #177a03;
        margin-right: 8px;
    }
    .results-layout {
        display: grid;
        grid-template-columns: 40% 55%;
        gap: 20px;
    }
    .results-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        box-shadow: 0 1px 5px rgba(0, 0, 0, 0.08);
    }
    .results-section h3 {
        margin-top: 0;
        margin-bottom: 20px;
        color: #333;
        font-weight: 600;
        font-size: 16px;
        border-bottom: 2px solid #177a03;
        padding-bottom: 10px;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        color: #333;
        font-weight: 600;
        font-size: 13px;
    }
    .form-group select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 13px;
        font-family: inherit;
        transition: all 0.2s;
    }
    .form-group select:focus {
        outline: none;
        border-color: #177a03;
        box-shadow: 0 0 0 2px rgba(23, 122, 3, 0.1);
    }
    .message {
        background: #d4edda;
        color: #155724;
        padding: 12px 15px;
        border-radius: 6px;
        margin-bottom: 15px;
        font-size: 13px;
        border: 1px solid #c3e6cb;
    }
    .message.error {
        background: #f8d7da;
        color: #721c24;
        border-color: #f5c6cb;
    }
    .results-table-wrapper {
        overflow-x: auto;
    }
    .results-table-wrapper table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        background: white;
        border-radius: 8px;
        overflow: hidden;
    }
    .results-table-wrapper table th {
        background: #177a03;
        color: white;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
    }
    .results-table-wrapper table td {
        padding: 12px;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }
    .results-table-wrapper table tr:hover {
        background: #f9f9f9;
    }
    .mark-input {
        width: 80%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        text-align: center;
        font-weight: 500;
        font-size: 13px;
    }
    .mark-input:focus {
        outline: none;
        border-color: #177a03;
        box-shadow: 0 0 0 2px rgba(23, 122, 3, 0.1);
    }
    .submit-btn {
        background: #177a03;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        margin-top: 20px;
        display: block;
        width: 100%;
        font-size: 14px;
    }
    .submit-btn:hover {
        background: #145a02;
    }
    .submit-btn i {
        margin-right: 8px;
    }
    @media (max-width: 968px) {
        .results-layout {
            grid-template-columns: 1fr;
        }
    }
</style>

<style>
    /* Mobile Bottom Navigation */
    .mobile-bottom-nav { display: none; }
    @media (max-width: 1024px) {
        .mobile-bottom-nav {
            display: flex !important;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
            justify-content: space-around;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 -4px 10px rgba(0,0,0,0.1);
        }
        .mobile-bottom-nav a {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 9px;
            padding: 4px 4px;
            border-radius: 8px;
            transition: all 0.2s ease;
            flex: 1;
            text-align: center;
        }
        .mobile-bottom-nav a i { font-size: 16px; margin-bottom: 2px; }
        .mobile-bottom-nav a:hover, .mobile-bottom-nav a.active { color: white; background: rgba(255,255,255,0.15); }
        .main-content { padding-bottom: 70px !important; }
    }
</style>

<div class="manage-results-container">
    <div class="page-title"><i class="fas fa-clipboard-list"></i> Manage Results</div>

    <form method="post">
        <div class="results-layout">
            <div class="results-section">
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

                <input type="hidden" name="batch_year" id="batch_year" value="<?= htmlspecialchars($batch_year) ?>">
            </div>

            <div class="results-section">
                <h3>Enter Marks</h3>
                <div id="subjectTableArea">
                    <p style="color: #666; text-align: center; padding: 40px 20px; background: #f9f9f9; border-radius: 8px;">
                        <i class="fas fa-info-circle"></i> Select Batch, Class, Student, and Result Type to enter marks
                    </p>
                </div>
            </div>
        </div>

        <button type="submit" id="submitBtn" class="submit-btn" style="display:none;">
            <i class="fas fa-save"></i> Submit Results
        </button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        function loadStudents(batchId, classId) {
            if (!batchId || !classId) {
                $('#student_id').html('<option value="">Select Student</option>');
                return;
            }

            $.post('../student/get_students_by_batch_class.php', {
                batch_id: batchId,
                class_id: classId
            }, function (data) {
                $('#student_id').html(data);
            }).fail(function () {
                $('#student_id').html('<option value="">Error loading students</option>');
            });
        }

        function loadSubjects(classId, studentId, batchYear, resultType) {
            if (!classId || !studentId || !batchYear || !resultType) {
                $('#subjectTableArea').html('<p style="color: #666; text-align: center; padding: 40px 20px; background: #f9f9f9; border-radius: 8px;"><i class="fas fa-info-circle"></i> Select all options to enter marks</p>');
                $('#submitBtn').hide();
                return;
            }
            $.post('get_subjects_for_class.php', {
                class_id: classId,
                student_id: studentId,
                batch_year: batchYear,
                result_type: resultType
            }, function(data) {
                if (data.indexOf('Please select') !== -1 || data.indexOf('Invalid') !== -1 || data.indexOf('No subjects') !== -1 || data.indexOf('not authorized') !== -1) {
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
                $.post('get_batch_year.php', {
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

<script>
    function confirmLogout() {
        var result = confirm('Are you sure you want to logout?');
        if (result) {
            window.location.href = 'logout.php';
        }
    }
</script>

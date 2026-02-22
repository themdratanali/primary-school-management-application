<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
include '../config/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: ../admin/login.php');
    exit;
}

$classes = $conn->query("SELECT * FROM classes ORDER BY name");
$batches = $conn->query("SELECT * FROM batches ORDER BY name");

$message = '';
$errors = [];

$name = $mother_name = $father_name = $gender = $dob = '';
$birth_cert_no = $blood_group = $religion = $nationality = '';
$present_address = $permanent_address = '';
$roll = $batch_id = $class_id = 0;
$session = '';

$father_mobile = '';
$mother_mobile = '';

$guardian_name = $guardian_profession = '';
$guardian_mobile = $guardian_address = '';

$student_login_email = '';
$student_login_password = '';

$photo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

    if (!ams_csrf_is_valid($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token.";
    }

    $name = trim($_POST['name'] ?? '');
    $mother_name = trim($_POST['mother_name'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';

    $birth_cert_no = trim($_POST['birth_cert_no'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');
    $religion = trim($_POST['religion'] ?? '');
    $nationality = trim($_POST['nationality'] ?? '');

    $present_address = trim($_POST['present_address'] ?? '');
    $permanent_address = trim($_POST['permanent_address'] ?? '');

    $roll = (int)($_POST['roll'] ?? 0);
    $batch_id = (int)($_POST['batch_id'] ?? 0);
    $class_id = (int)($_POST['class_id'] ?? 0);

    $father_mobile = trim($_POST['father_mobile'] ?? '');
    $mother_mobile = trim($_POST['mother_mobile'] ?? '');

    $guardian_name = trim($_POST['guardian_name'] ?? '');
    $guardian_profession = trim($_POST['guardian_profession'] ?? '');
    $guardian_mobile = trim($_POST['guardian_mobile'] ?? '');
    $guardian_address = trim($_POST['guardian_address'] ?? '');

    $student_login_email = trim($_POST['student_login_email'] ?? '');
    $student_login_password = $_POST['student_login_password'] ?? '';

    if ($name === '') $errors[] = "Student name is required.";
    if ($mother_name === '') $errors[] = "Mother name is required.";
    if ($father_name === '') $errors[] = "Father name is required.";
    if ($gender === '') $errors[] = "Gender is required.";
    if ($dob === '') $errors[] = "Date of birth is required.";
    if ($present_address === '') $errors[] = "Present address is required.";
    if ($permanent_address === '') $errors[] = "Permanent address is required.";
    if ($batch_id === 0) $errors[] = "Batch is required.";
    if ($class_id === 0) $errors[] = "Class is required.";
    if ($roll === 0) $errors[] = "Roll is required.";
    if ($father_mobile === '') $errors[] = "Father mobile is required.";

    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $errors[] = "Invalid photo format.";
        } else {
            $batch_name = $conn->query("SELECT name FROM batches WHERE id=$batch_id")->fetch_assoc()['name'] ?? 'unknown-batch';
            $class_name = $conn->query("SELECT name FROM classes WHERE id=$class_id")->fetch_assoc()['name'] ?? 'unknown-class';

            $batch_name_safe = preg_replace('/\s+/', '-', $batch_name);
            $class_name_safe = preg_replace('/\s+/', '-', $class_name);
            $upload_dir = "../uploads/students/{$batch_name_safe}-{$class_name_safe}";

            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $student_name_safe = preg_replace('/\s+/', '-', $name ?: 'student');
            $photo_base = "{$upload_dir}/{$student_name_safe}";
            $photo_file = $photo_base . ".{$ext}";
            $i = 1;
            while (file_exists($photo_file)) {
                $photo_file = $photo_base . "_{$i}.{$ext}";
                $i++;
            }
            $photo = $photo_file;

            move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
        }
    }

    if (empty($errors)) {
        $batch_name = $conn->query("SELECT name FROM batches WHERE id=$batch_id")->fetch_assoc()['name'];
        $class_name = $conn->query("SELECT name FROM classes WHERE id=$class_id")->fetch_assoc()['name'];

        $table_name = "Student_" . preg_replace('/\s+/', '', $batch_name) . "_" . preg_replace('/\s+/', '', $class_name);
        $conn->query("CREATE TABLE IF NOT EXISTS `$table_name` LIKE students");
        
        // Check if the dynamic table has birth_cert_no column, if not add it
        $check_col = $conn->query("SHOW COLUMNS FROM `$table_name` LIKE 'birth_cert_no'");
        if ($check_col->num_rows == 0) {
            $conn->query("ALTER TABLE `$table_name` ADD COLUMN birth_cert_no VARCHAR(50) DEFAULT '' AFTER dob");
        }
        
        $stmt = $conn->prepare("INSERT INTO `$table_name`
            (name, mother_name, father_name, gender, dob,
            birth_cert_no, blood_group, religion, nationality,
            present_address, permanent_address,
            roll, batch_id, class_id,
            father_mobile, mother_mobile,
            guardian_name, guardian_profession,
            guardian_mobile, photo)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        $stmt->bind_param(
            "sssssssssssiiissssss",
            $name,
            $mother_name,
            $father_name,
            $gender,
            $dob,
            $birth_cert_no,
            $blood_group,
            $religion,
            $nationality,
            $present_address,
            $permanent_address,
            $roll,
            $batch_id,
            $class_id,
            $father_mobile,
            $mother_mobile,
            $guardian_name,
            $guardian_profession,
            $guardian_mobile,
            $photo
        );

        if ($stmt->execute()) {
            $student_id = $stmt->insert_id;

            if ($student_id > 0 && $student_login_email && $student_login_password !== '') {
                $password_hash = password_hash($student_login_password, PASSWORD_DEFAULT);
                $user_stmt = $conn->prepare("
                    INSERT INTO student_users (student_id, email, password, plain_password)
                    VALUES (?, ?, ?, ?)
                ");
                $user_stmt->bind_param("isss", $student_id, $student_login_email, $password_hash, $student_login_password);
                $user_stmt->execute();
                $user_stmt->close();
            }

            $message = "Student added successfully.";
            $name = $mother_name = $father_name = $gender = $dob = '';
            $birth_cert_no = $blood_group = $religion = $nationality = '';
            $present_address = $permanent_address = '';
            $roll = $batch_id = $class_id = 0;
            $father_mobile = $mother_mobile = '';
            $guardian_name = $guardian_profession = '';
            $guardian_mobile = $guardian_address = '';
            $student_login_email = '';
            $student_login_password = '';
            $photo = '';
        } else {
            $errors[] = "Database insert failed.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Add Student</title>
    <link rel="stylesheet" href="../assets/css/add_student.css">
</head>
<body>
<div class="container">
    <h2>Student Admission Form</h2>

    <?php if ($message): ?>
        <p class="message-success"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="message-error">
            <?php foreach ($errors as $err): ?>
                <div><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" novalidate>
        <?= ams_csrf_field() ?>

        <fieldset>
            <legend>Personal Information</legend>
            <div class="row">
                <div><label for="name">Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required>
                </div>
                <div><label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?= $gender==='Male'?'selected':'' ?>>Male</option>
                        <option value="Female" <?= $gender==='Female'?'selected':'' ?>>Female</option>
                        <option value="Other" <?= $gender==='Other'?'selected':'' ?>>Other</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div><label for="mother_name">Mother's Name</label>
                    <input type="text" id="mother_name" name="mother_name" value="<?= htmlspecialchars($mother_name) ?>" required>
                </div>
                <div><label for="father_name">Father's Name</label>
                    <input type="text" id="father_name" name="father_name" value="<?= htmlspecialchars($father_name) ?>" required>
                </div>
            </div>

            <div class="row">
                <div><label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($dob) ?>" required>
                </div>
                <div><label for="birth_cert_no">Birth Certificate No.</label>
                    <input type="text" id="birth_cert_no" name="birth_cert_no" value="<?= htmlspecialchars($birth_cert_no) ?>">
                </div>
            </div>

            <div class="row">
                <div><label for="blood_group">Blood Group</label>
                    <input type="text" id="blood_group" name="blood_group" value="<?= htmlspecialchars($blood_group) ?>">
                </div>
                <div><label for="religion">Religion</label>
                    <input type="text" id="religion" name="religion" value="<?= htmlspecialchars($religion) ?>">
                </div>
            </div>

            <div class="row">
                <div><label for="nationality">Nationality</label>
                    <input type="text" id="nationality" name="nationality" value="<?= htmlspecialchars($nationality) ?>">
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Address</legend>
            <label for="present_address">Present Address</label>
            <textarea id="present_address" name="present_address" required><?= htmlspecialchars($present_address) ?></textarea>

            <label for="permanent_address">Permanent Address</label>
            <textarea id="permanent_address" name="permanent_address" required><?= htmlspecialchars($permanent_address) ?></textarea>
        </fieldset>

        <fieldset>
            <legend>Academic Information</legend>
            <label>Batch:</label>
            <select name="batch_id" id="batch_id" required>
                <option value="">Select Batch</option>
                <?php
                $batches->data_seek(0);
                while ($b = $batches->fetch_assoc()):
                    ?>
                    <option value="<?= $b['id'] ?>" <?= $batch_id==$b['id']?'selected':'' ?>><?= htmlspecialchars($b['name']) ?></option>
                <?php endwhile; ?>
            </select>

            <label>Class:</label>
            <select name="class_id" id="class_id" required>
                <option value="">Select Class</option>
                <?php
                $classes->data_seek(0);
                while ($c = $classes->fetch_assoc()):
                    ?>
                    <option value="<?= $c['id'] ?>" <?= $class_id==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endwhile; ?>
            </select>

            <label>Roll:</label>
            <input type="number" name="roll" id="roll" readonly placeholder="Auto-generated" value="<?= htmlspecialchars($roll) ?>">
        </fieldset>

        <fieldset>
            <legend>Contact Information</legend>
            <label for="father_mobile">Father Mobile</label>
            <input type="text" id="father_mobile" name="father_mobile" value="<?= htmlspecialchars($father_mobile) ?>" />

            <label for="mother_mobile">Mother Mobile</label>
            <input type="text" id="mother_mobile" name="mother_mobile" value="<?= htmlspecialchars($mother_mobile) ?>" />
        </fieldset>

        <fieldset>
            <legend>Local Guardian</legend>
            <label for="guardian_name">Guardian Name</label>
            <input type="text" id="guardian_name" name="guardian_name" value="<?= htmlspecialchars($guardian_name) ?>" />

            <label for="guardian_profession">Guardian Profession</label>
            <input type="text" id="guardian_profession" name="guardian_profession" value="<?= htmlspecialchars($guardian_profession) ?>" />

            <label for="guardian_mobile">Guardian Mobile</label>
            <input type="text" id="guardian_mobile" name="guardian_mobile" value="<?= htmlspecialchars($guardian_mobile) ?>" />
        </fieldset>

        <fieldset>
            <legend>Student Login (optional)</legend>
            <p class="help-text">If you set an email and password, the student will be able to log in to the portal.</p>
            <label for="student_login_email">Login Email</label>
            <input type="email" id="student_login_email" name="student_login_email" value="<?= htmlspecialchars($student_login_email) ?>" placeholder="student@example.com" />

            <label for="student_login_password">Login Password</label>
            <input type="text" id="student_login_password" name="student_login_password" value="<?= htmlspecialchars($student_login_password) ?>" placeholder="Set a temporary password" />
        </fieldset>

        <fieldset>
            <legend>Photo</legend>
            <?php if ($photo && file_exists($photo)): ?>
                <div>
                    <img src="<?= htmlspecialchars($photo) ?>" alt="Student Photo" style="max-width:150px; display:block; margin-bottom:5px;">
                </div>
            <?php endif; ?>
            <label for="photo">Upload Photo</label>
            <input type="file" id="photo" name="photo" accept="image/*" />
        </fieldset>

        <button class="generatebutton" type="submit" name="submit">Add Student</button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        function fetchNextRoll() {
            let batchId = $('#batch_id').val();
            let classId = $('#class_id').val();

            if (batchId && classId) {
                $.ajax({
                    url: 'get_next_roll.php',
                    type: 'POST',
                    data: { batch_id: batchId, class_id: classId },
                    success: function(response) { $('#roll').val(response); }
                });
            } else {
                $('#roll').val('');
            }
        }
        $('#batch_id, #class_id').on('change', fetchNextRoll);
        fetchNextRoll();
    });
</script>
</body>
</html>

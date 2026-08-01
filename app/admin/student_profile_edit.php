<?php
require_once __DIR__ . '/../../env/session.php';
include '../../env/config.php';

if (!isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

$table = $_GET['table'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (!$table || !$id) {
    die("Invalid request.");
}

$stmt = $conn->prepare("SELECT * FROM `$table` WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Student not found.");
}

$student = $result->fetch_assoc();
$stmt->close();

$batch_name = '';
$class_name = '';
$batch_id = 0;
$class_id = 0;

if (preg_match('/Student_(\d+)_(\d+)/', $table, $matches)) {
    $batch_id = intval($matches[1]);
    $class_id = intval($matches[2]);
    
    $stmtBatch = $conn->prepare("SELECT name FROM batches WHERE id = ?");
    $stmtBatch->bind_param("i", $batch_id);
    $stmtBatch->execute();
    $resBatch = $stmtBatch->get_result();
    if ($resBatch->num_rows > 0) {
        $batch_name = $resBatch->fetch_assoc()['name'];
    }
    $stmtBatch->close();

    $stmtClass = $conn->prepare("SELECT name FROM classes WHERE id = ?");
    $stmtClass->bind_param("i", $class_id);
    $stmtClass->execute();
    $resClass = $stmtClass->get_result();
    if ($resClass->num_rows > 0) {
        $class_name = $resClass->fetch_assoc()['name'];
    }
    $stmtClass->close();
}

$classes = $conn->query("SELECT * FROM classes");
$batches = $conn->query("SELECT * FROM batches");

$message = '';

if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $mother_name = $_POST['mother_name'];
    $father_name = $_POST['father_name'];
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $birth_cert_no = $_POST['birth_cert_no'];
    $blood_group = $_POST['blood_group'];
    $religion = $_POST['religion'];
    $nationality = $_POST['nationality'];
    $present_address = $_POST['present_address'];
    $permanent_address = $_POST['permanent_address'];
    $roll = $_POST['roll'];
    $father_mobile = $_POST['father_mobile'] ?? '';
    $mother_mobile = $_POST['mother_mobile'] ?? '';
    $guardian_name = $_POST['guardian_name'];
    $guardian_profession = $_POST['guardian_profession'];
    $guardian_mobile = $_POST['guardian_mobile'];
    $guardian_address = $_POST['guardian_address'];

    $photo = $student['photo'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $newPhoto = ams_upload_dir('students') . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $newPhoto)) {
            if (!empty($student['photo']) && file_exists(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $student['photo']))) {
                unlink(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $student['photo']));
            }
            $photo = 'uploads/students/' . basename($newPhoto);
        }
    }

    $docStmt = $conn->prepare("SELECT file_path FROM students_documents WHERE student_id = ?");
    $docStmt->bind_param("i", $id);
    $docStmt->execute();
    $docResult = $docStmt->get_result();
    while ($docRow = $docResult->fetch_assoc()) {
        $docFsPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $docRow['file_path']);
        if (file_exists($docFsPath)) {
            unlink($docFsPath);
        }
    }
    $docStmt->close();
    $conn->query("DELETE FROM students_documents WHERE student_id = $id");

    if (isset($_FILES['document']) && $_FILES['document']['error'][0] == 0) {
        foreach ($_FILES['document']['name'] as $idx => $docName) {
            if ($_FILES['document']['error'][$idx] == 0) {
                $docExt = pathinfo($docName, PATHINFO_EXTENSION);
                $docPath = ams_upload_dir('students/documents') . $id . '_' . time() . '_' . $idx . '.' . $docExt;
                if (move_uploaded_file($_FILES['document']['tmp_name'][$idx], $docPath)) {
                    $docUrl = 'uploads/students/documents/' . basename($docPath);
                    $insDoc = $conn->prepare("INSERT INTO students_documents (student_id, file_name, file_path) VALUES (?, ?, ?)");
                    $insDoc->bind_param("iss", $id, $docName, $docUrl);
                    $insDoc->execute();
                    $insDoc->close();
                }
            }
        }
    }

    $stmt = $conn->prepare("UPDATE `$table` SET 
        name=?, mother_name=?, father_name=?, gender=?, dob=?, birth_cert_no=?, blood_group=?, religion=?, nationality=?,
        present_address=?, permanent_address=?, roll=?, 
        father_mobile=?, mother_mobile=?, guardian_name=?, guardian_profession=?, guardian_mobile=?, guardian_address=?, photo=?
        WHERE id=?");

    $types = str_repeat('s', 11) . 'i' . str_repeat('s', 7) . 'i';
    $stmt->bind_param(
        $types,
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
        $father_mobile,
        $mother_mobile,
        $guardian_name,
        $guardian_profession,
        $guardian_mobile,
        $guardian_address,
        $photo,
        $id
    );

    if ($stmt->execute()) {
        $message = "Student information updated successfully.";
    } else {
        $message = "Error updating student: " . $stmt->error;
    }
}

$photo = '';
if (!empty($student['photo'])) {
    $photoPath = $student['photo'];
    $cleanPath = str_replace('../', '', $photoPath);
    $fsPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);
    if (file_exists($fsPath)) {
        $photo = BASE_URL . '/' . $cleanPath;
    }
}
if (empty($photo)) {
    $photo = BASE_URL . '/uploads/students/default-photo.jpg';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit Student Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/library/css/student_profile_card.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/library/css/student_profile_edit.css">
</head>
<body class="sp-card-body">
    <div class="sp-card-wrapper">
        <div class="profile-card">
            <div class="profile-id" style="padding-top: 22px;">
                <h2 class="profile-name"><i class="fas fa-user-edit"></i> Edit Student Profile</h2>
                <p class="profile-roll"><?= htmlspecialchars($student['name']) ?></p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="sp-alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="profile-section">
                <h4><i class="fas fa-camera"></i> Photo & Documents</h4>
                <div class="form-grid">
                    <label class="form-field full">
                        Current Photo
                        <img src="<?= htmlspecialchars($photo) ?>" alt="Current Photo" class="photo-preview">
                    </label>
                    <label class="form-field">New Photo
                        <input type="file" name="photo" accept="image/*">
                    </label>
                    <label class="form-field">Documents (replace existing)
                        <input type="file" name="document[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple>
                    </label>
                </div>
            </div>

            <div class="profile-section">
                <h4><i class="fas fa-user"></i> Personal Information</h4>
                <div class="form-grid">
                    <label class="form-field">Name
                        <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required>
                    </label>
                    <label class="form-field">Gender
                        <select name="gender" required>
                            <option value="">Select</option>
                            <option value="Male" <?= $student['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= $student['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= $student['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </label>
                    <label class="form-field">Mother's Name
                        <input type="text" name="mother_name" value="<?= htmlspecialchars($student['mother_name']) ?>" required>
                    </label>
                    <label class="form-field">Father's Name
                        <input type="text" name="father_name" value="<?= htmlspecialchars($student['father_name']) ?>" required>
                    </label>
                    <label class="form-field">Date of Birth
                        <input type="date" name="dob" value="<?= htmlspecialchars($student['dob']) ?>" required>
                    </label>
                    <label class="form-field">Birth Certificate No.
                        <input type="text" name="birth_cert_no" value="<?= htmlspecialchars($student['birth_cert_no']) ?>">
                    </label>
                    <label class="form-field">Blood Group
                        <input type="text" name="blood_group" value="<?= htmlspecialchars($student['blood_group']) ?>">
                    </label>
                    <label class="form-field">Religion
                        <input type="text" name="religion" value="<?= htmlspecialchars($student['religion']) ?>">
                    </label>
                    <label class="form-field">Nationality
                        <input type="text" name="nationality" value="<?= htmlspecialchars($student['nationality']) ?>">
                    </label>
                </div>
            </div>

            <div class="profile-section">
                <h4><i class="fas fa-map-marker-alt"></i> Address</h4>
                <div class="form-grid">
                    <label class="form-field">Present Address
                        <textarea name="present_address" required><?= htmlspecialchars($student['present_address']) ?></textarea>
                    </label>
                    <label class="form-field">Permanent Address
                        <textarea name="permanent_address" required><?= htmlspecialchars($student['permanent_address']) ?></textarea>
                    </label>
                </div>
            </div>

            <div class="profile-section">
                <h4><i class="fas fa-graduation-cap"></i> Academic Information</h4>
                <div class="form-grid">
                    <label class="form-field">Batch
                        <input type="text" value="<?= htmlspecialchars($batch_name) ?>" readonly>
                    </label>
                    <label class="form-field">Class
                        <input type="text" value="<?= htmlspecialchars($class_name) ?>" readonly>
                    </label>
                    <label class="form-field">Roll
                        <input type="text" name="roll" value="<?= htmlspecialchars($student['roll']) ?>" required>
                    </label>
                </div>
            </div>

            <div class="profile-section">
                <h4><i class="fas fa-phone"></i> Contact Information</h4>
                <div class="form-grid">
                    <label class="form-field">Father Mobile
                        <input type="text" name="father_mobile" value="<?= htmlspecialchars($student['father_mobile']) ?>">
                    </label>
                    <label class="form-field">Mother Mobile
                        <input type="text" name="mother_mobile" value="<?= htmlspecialchars($student['mother_mobile']) ?>">
                    </label>
                </div>
            </div>

            <div class="profile-section">
                <h4><i class="fas fa-user-friends"></i> Local Guardian</h4>
                <div class="form-grid">
                    <label class="form-field">Guardian Name
                        <input type="text" name="guardian_name" value="<?= htmlspecialchars($student['guardian_name']) ?>">
                    </label>
                    <label class="form-field">Guardian Profession
                        <input type="text" name="guardian_profession" value="<?= htmlspecialchars($student['guardian_profession']) ?>">
                    </label>
                    <label class="form-field">Guardian Mobile
                        <input type="text" name="guardian_mobile" value="<?= htmlspecialchars($student['guardian_mobile']) ?>">
                    </label>
                    <label class="form-field">Guardian Address
                        <textarea name="guardian_address"><?= htmlspecialchars($student['guardian_address']) ?></textarea>
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="submit" class="btn-update"><i class="fas fa-save"></i> Update Student</button>
            </div>
        </form>
    </div>
</body>
</html>
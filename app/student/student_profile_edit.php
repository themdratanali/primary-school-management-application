<?php
require_once __DIR__ . '/../../env/session.php';
include '../../env/config.php';

// Auth check - allow admin and student
if (!isset($_SESSION['admin']) && !isset($_SESSION['student_email'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

$is_admin = isset($_SESSION['admin']);
$is_student = isset($_SESSION['student_email']) && !$is_admin;

$id = 0;
$table = 'students';

if ($is_admin) {
    $id = intval($_GET['id'] ?? 0);
    $table = $_GET['table'] ?? 'students';
} elseif ($is_student) {
    $id = isset($_SESSION['student_dynamic_id']) ? (int)$_SESSION['student_dynamic_id'] : 0;
    $table = $_SESSION['student_table'] ?? 'students';
}

if (!$id) {
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
$batches = $conn->query("SELECT id, name FROM batches ORDER BY name");
$classes = $conn->query("SELECT id, name FROM classes ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $gender = $_POST['gender'];
    $mother_name = $_POST['mother_name'];
    $father_name = $_POST['father_name'];
    $dob = $_POST['dob'];
    $birth_cert_no = $_POST['birth_cert_no'];
    $blood_group = $_POST['blood_group'];
    $religion = $_POST['religion'];
    $nationality = $_POST['nationality'];
    $present_address = $_POST['present_address'];
    $permanent_address = $_POST['permanent_address'];
    $batch_id = intval($_POST['batch_id']);
    $class_id = intval($_POST['class_id']);
    $roll = $_POST['roll'];
    $father_mobile = $_POST['father_mobile'] ?? '';
    $mother_mobile = $_POST['mother_mobile'] ?? '';
    $guardian_name = $_POST['guardian_name'];
    $guardian_profession = $_POST['guardian_profession'];
    $guardian_mobile = $_POST['guardian_mobile'];

    $photo = $student['photo'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = ams_upload_dir('students');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $newPhotoName = uniqid('student_', true) . '.' . $ext;
        $uploadPath = $uploadDir . $newPhotoName;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
            if (!empty($student['photo']) && file_exists(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $student['photo']))) {
                unlink(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $student['photo']));
            }
            $photo = 'uploads/students/' . $newPhotoName;
        }
    }

    $stmt = $conn->prepare("UPDATE `$table` SET 
        name = ?, gender = ?, mother_name = ?, father_name = ?, dob = ?, birth_cert_no = ?,
        blood_group = ?, religion = ?, nationality = ?, present_address = ?, permanent_address = ?,
        batch_id = ?, class_id = ?, roll = ?, 
        father_mobile = ?, mother_mobile = ?, guardian_name = ?, guardian_profession = ?, 
        guardian_mobile = ?, photo = ?
        WHERE id = ?");

    $types = str_repeat('s', 11) . 'ii' . str_repeat('s', 6) . 'ii';
    $stmt->bind_param(
        $types,
        $name,
        $gender,
        $mother_name,
        $father_name,
        $dob,
        $birth_cert_no,
        $blood_group,
        $religion,
        $nationality,
        $present_address,
        $permanent_address,
        $batch_id,
        $class_id,
        $roll,
        $father_mobile,
        $mother_mobile,
        $guardian_name,
        $guardian_profession,
        $guardian_mobile,
        $photo,
        $id
    );

    if ($stmt->execute()) {
        header("Location: student_profile?table=$table&id=$id");
        exit;
    } else {
        echo "Update failed: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Student Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/student_profile_card.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/student_profile_edit.css">
</head>

<body class="sp-card-body">
    <div class="sp-card-wrapper">
        <div class="profile-card">
            <div class="profile-cover">
            </div>
            <div class="profile-id" style="padding-top: 22px;">
                <h2 class="profile-name"><i class="fas fa-user-edit"></i> Edit Student Profile</h2>
            </div>
        </div>

        <form method="post" enctype="multipart/form-data">
            <div class="profile-section">
                <h4><i class="fas fa-camera"></i> Photo</h4>
                <div class="form-grid">
                    <label class="form-field full">
                        Photo
                        <input type="file" name="photo" accept="image/*">
                        <?php if (!empty($student['photo'])): 
                            $previewPhoto = BASE_URL . '/uploads/students/default-photo.jpg';
                            $cleanPath = str_replace('../', '', $student['photo']);
                            $fsPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);
                            if (file_exists($fsPath)) {
                                $previewPhoto = BASE_URL . '/' . $cleanPath;
                            }
                        ?>
                            <img src="<?= htmlspecialchars($previewPhoto) ?>" alt="Photo" class="photo-preview">
                        <?php endif; ?>
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
                            <option value="Male" <?= ($student['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($student['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($student['gender'] == 'Other') ? 'selected' : '' ?>>Other</option>
                        </select>
                    </label>
                    <label class="form-field">Mother's Name
                        <input type="text" name="mother_name" value="<?= htmlspecialchars($student['mother_name']) ?>">
                    </label>
                    <label class="form-field">Father's Name
                        <input type="text" name="father_name" value="<?= htmlspecialchars($student['father_name']) ?>">
                    </label>
                    <label class="form-field">Date of Birth
                        <input type="date" name="dob" value="<?= htmlspecialchars($student['dob']) ?>">
                    </label>
                    <label class="form-field">Birth Certificate No
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
                        <textarea name="present_address"><?= htmlspecialchars($student['present_address']) ?></textarea>
                    </label>
                    <label class="form-field">Permanent Address
                        <textarea name="permanent_address"><?= htmlspecialchars($student['permanent_address']) ?></textarea>
                    </label>
                </div>
            </div>

            <div class="profile-section">
                <h4><i class="fas fa-graduation-cap"></i> Academic Information</h4>
                <div class="form-grid">
                    <label class="form-field">Batch
                        <select name="batch_id">
                            <option value="">Select Batch</option>
                            <?php while ($row = $batches->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>" <?= ($student['batch_id'] == $row['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </label>
                    <label class="form-field">Class
                        <select name="class_id">
                            <option value="">Select Class</option>
                            <?php while ($row = $classes->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>" <?= ($student['class_id'] == $row['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </label>
                    <label class="form-field">Roll
                        <input type="text" name="roll" value="<?= htmlspecialchars($student['roll']) ?>">
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
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-update"><i class="fas fa-save"></i> Update Profile</button>
            </div>
        </form>
    </div>
</body>

</html>











<?php
require_once __DIR__ . '/../../env/session.php';
include '../../env/config.php';

// Auth check
if (!isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

// Validate staff ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Staff ID missing or invalid");
}

// Fetch staff data
$id = (int)$_GET['id'];

$sql = "SELECT * FROM staff WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Staff not found.");
}

$clean_photo = !empty($data['photo']) ? str_replace('../', '', $data['photo']) : '';
$fsPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $clean_photo);
$photo = (!empty($clean_photo) && file_exists($fsPath)) ? BASE_URL . '/' . $clean_photo : ams_upload_url('staff', 'default-photo.jpg');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $mother_name = $_POST['mother_name'] ?? '';
    $father_name = $_POST['father_name'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $blood_group = $_POST['blood_group'] ?? '';
    $religion = $_POST['religion'] ?? '';
    $nationality = $_POST['nationality'] ?? '';
    $nid = $_POST['nid'] ?? '';
    $present_address = $_POST['present_address'] ?? '';
    $permanent_address = $_POST['permanent_address'] ?? '';
    $education = $_POST['education'] ?? '';
    $experience = $_POST['experience'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $designation = $_POST['designation'] ?? '';

    $staff_name_safe = preg_replace('/[^A-Za-z0-9\-]/', '-', strtolower($name ?: 'staff'));
    if (!empty($_FILES['photo']['name'])) {
        $filename = ams_safe_filename($_FILES['photo']['name']);
        $targetPath = ams_upload_dir('staff/' . $staff_name_safe) . $filename;
        move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath);
        $photoName = 'uploads/staff/' . $staff_name_safe . '/' . $filename;
    } else {
        $photoName = $data['photo'];
    }

    // Upload documents
    if (!empty($_FILES['documents']['name'][0])) {
        $allowed_ext = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
        $upload_base = ams_upload_dir('staff/' . $staff_name_safe . '/');
        $doc_stmt = $conn->prepare("INSERT INTO staff_documents (staff_id, file_name, file_path) VALUES (?, ?, ?)");
        for ($i = 0; $i < count($_FILES['documents']['name']); $i++) {
            if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_OK) {
                $file_ext = strtolower(pathinfo($_FILES['documents']['name'][$i], PATHINFO_EXTENSION));
                if (in_array($file_ext, $allowed_ext) && $_FILES['documents']['size'][$i] <= 2 * 1024 * 1024) {
                    $doc_filename = uniqid('doc_', true) . '.' . $file_ext;
                    $doc_path = 'uploads/staff/' . $staff_name_safe . '/' . $doc_filename;
                    $doc_full_path = $upload_base . $doc_filename;
                    if (move_uploaded_file($_FILES['documents']['tmp_name'][$i], $doc_full_path)) {
                        $doc_stmt->bind_param("iss", $id, $_FILES['documents']['name'][$i], $doc_path);
                        $doc_stmt->execute();
                    }
                }
            }
        }
        $doc_stmt->close();
    }

    $update_sql = "UPDATE staff SET name=?, gender=?, mother_name=?, father_name=?, dob=?, blood_group=?, religion=?, nationality=?, nid=?, present_address=?, permanent_address=?, education=?, experience=?, phone=?, designation=?, photo=? WHERE id=?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssssssssssssssi", $name, $gender, $mother_name, $father_name, $dob, $blood_group, $religion, $nationality, $nid, $present_address, $permanent_address, $education, $experience, $phone, $designation, $photoName, $id);

    if ($update_stmt->execute()) {
        header("Location: staff_profile?id=$id");
        exit;
    } else {
        echo "Error updating staff profile.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Staff Profile - <?= htmlspecialchars($data['name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/library/css/profile_card.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/library/css/profile_edit.css">
</head>

<body class="ams-card-body">
    <div class="ams-card-wrapper">
        <div class="profile-card">
            <div class="profile-id" style="padding-top: 22px;">
                <h2 class="profile-name"><i class="fas fa-user-edit"></i> Edit Staff Profile</h2>
                <p class="profile-roll"><?= htmlspecialchars($data['name']) ?></p>
            </div>
        </div>

        <form method="post" enctype="multipart/form-data">
            <div class="profile-section">
                <h4><i class="fas fa-camera"></i> Photo & Documents</h4>
                <div class="form-grid">
                    <label class="form-field full">Current Photo
                        <img src="<?= htmlspecialchars($photo) ?>" alt="Staff Photo" class="photo-preview">
                    </label>
                    <label class="form-field">Change Photo (optional)
                        <input type="file" name="photo" accept="image/*">
                    </label>
                    <label class="form-field">Upload Documents (PDF, DOC, JPG, PNG - max 2MB each)
                        <input type="file" name="documents[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif" multiple>
                    </label>
                    <label class="form-field" style="grid-column: 1 / -1;">
                        <span class="form-hint">You can select multiple files. Allowed formats: PDF, DOC, DOCX, JPG, PNG, GIF</span>
                    </label>
                </div>
            </div>

            <div class="profile-section">
                <h4><i class="fas fa-user"></i> Personal Information</h4>
                <div class="form-grid">
                    <label class="form-field">Name
                        <input type="text" name="name" value="<?= htmlspecialchars($data['name']) ?>" required>
                    </label>
                    <label class="form-field">Gender
                        <select name="gender">
                            <option value="">Select Gender</option>
                            <option value="Male" <?= ($data['gender'] === 'Male') ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($data['gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($data['gender'] === 'Other') ? 'selected' : '' ?>>Other</option>
                        </select>
                    </label>
                    <label class="form-field">Mother's Name
                        <input type="text" name="mother_name" value="<?= htmlspecialchars($data['mother_name']) ?>">
                    </label>
                    <label class="form-field">Father's Name
                        <input type="text" name="father_name" value="<?= htmlspecialchars($data['father_name']) ?>">
                    </label>
                    <label class="form-field">Date of Birth
                        <input type="date" name="dob" value="<?= htmlspecialchars($data['dob']) ?>">
                    </label>
                    <label class="form-field">Blood Group
                        <input type="text" name="blood_group" value="<?= htmlspecialchars($data['blood_group']) ?>">
                    </label>
                    <label class="form-field">Religion
                        <input type="text" name="religion" value="<?= htmlspecialchars($data['religion']) ?>">
                    </label>
                    <label class="form-field">Nationality
                        <input type="text" name="nationality" value="<?= htmlspecialchars($data['nationality']) ?>">
                    </label>
                    <label class="form-field">NID
                        <input type="text" name="nid" value="<?= htmlspecialchars($data['nid']) ?>">
                    </label>
                </div>
            </div>

            <div class="profile-section">
                <h4><i class="fas fa-map-marker-alt"></i> Address</h4>
                <div class="form-grid">
                    <label class="form-field">Present Address
                        <textarea name="present_address" rows="2"><?= htmlspecialchars($data['present_address']) ?></textarea>
                    </label>
                    <label class="form-field">Permanent Address
                        <textarea name="permanent_address" rows="2"><?= htmlspecialchars($data['permanent_address']) ?></textarea>
                    </label>
                </div>
            </div>

            <div class="profile-section">
                <h4><i class="fas fa-briefcase"></i> Professional Information</h4>
                <div class="form-grid">
                    <label class="form-field">Designation
                        <input type="text" name="designation" value="<?= htmlspecialchars($data['designation']) ?>" required>
                    </label>
                    <label class="form-field full">Education
                        <textarea name="education" rows="2"><?= htmlspecialchars($data['education']) ?></textarea>
                    </label>
                    <label class="form-field full">Experience
                        <textarea name="experience" rows="2"><?= htmlspecialchars($data['experience']) ?></textarea>
                    </label>
                </div>
            </div>

            <div class="profile-section">
                <h4><i class="fas fa-phone"></i> Contact Information</h4>
                <div class="form-grid">
                    <label class="form-field">Phone
                        <input type="text" name="phone" value="<?= htmlspecialchars($data['phone']) ?>">
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-update"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</body>

</html>









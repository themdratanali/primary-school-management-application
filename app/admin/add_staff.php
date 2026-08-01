<?php

require_once __DIR__ . '/../../env/session.php';
require_once __DIR__ . '/../../env/csrf.php';
include '../../env/config.php';

// Auth check
if (!isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

$message = '';
$photo = '';

// Handle staff creation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ams_csrf_verify_post();
    $name = trim($_POST['name']);
    $mother_name = trim($_POST['mother_name'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $gender = $_POST['gender'] ?? null;
    $dob = $_POST['dob'] ?? null;
    $blood_group = trim($_POST['blood_group'] ?? '');
    $religion = trim($_POST['religion'] ?? '');
    $nationality = trim($_POST['nationality'] ?? '');
    $nid = trim($_POST['nid'] ?? '');
    $present_address = trim($_POST['present_address'] ?? '');
    $permanent_address = trim($_POST['permanent_address'] ?? '');
    $education = trim($_POST['education'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $phone = trim($_POST['phone']);
    $designation = trim($_POST['designation'] ?? '');

    if (empty($name) || empty($phone) || empty($gender) || empty($designation)) {
        $message = "Please fill all required fields (Name, Gender, Phone, Designation).";
    } else {
        $staff_name_safe = preg_replace('/[^A-Za-z0-9\-]/', '-', strtolower($name ?: 'staff'));
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

            if (in_array($file_ext, $allowed_ext)) {
                if ($_FILES['photo']['size'] <= 2 * 1024 * 1024) {
                    $upload_dir = ams_upload_dir('staff/' . $staff_name_safe);
                    $filename = uniqid('staff_', true) . '.' . $file_ext;
                    $photo = 'uploads/staff/' . $staff_name_safe . '/' . $filename;
                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename)) {
                        $message = "Failed to upload photo.";
                    }
                } else {
                    $message = "Photo must be under 2MB.";
                }
            } else {
                $message = "Invalid photo format. Allowed: jpg, jpeg, png, gif.";
            }
        }
    }

    if (empty($message)) {
        $stmt = $conn->prepare("INSERT INTO staff (
            name, mother_name, father_name, gender, dob, blood_group, religion,
            nationality, nid, present_address, permanent_address, education, experience,
            phone, designation, photo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if ($stmt === false) {
            $message = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param(
                "ssssssssssssssss",
                $name,
                $mother_name,
                $father_name,
                $gender,
                $dob,
                $blood_group,
                $religion,
                $nationality,
                $nid,
                $present_address,
                $permanent_address,
                $education,
                $experience,
                $phone,
                $designation,
                $photo
            );
            if ($stmt->execute()) {
                $staff_id = $stmt->insert_id;
                
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
                                    $doc_stmt->bind_param("iss", $staff_id, $_FILES['documents']['name'][$i], $doc_path);
                                    $doc_stmt->execute();
                                }
                            }
                        }
                    }
                    $doc_stmt->close();
                }
                
                $message = "Staff added successfully!";
            } else {
                $message = "Execute failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Add Staff - Apex Model School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/add_teacher.css">
</head>

<body>
    <div class="container">
        <h2>Staff Form</h2>
        
        <?php if (!empty($message)): ?>
            <div class="alert <?= strpos($message, '✅') !== false || strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-danger' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" novalidate>
            <?= ams_csrf_field() ?>
            <fieldset>
                <legend>Personal Information</legend>
                <div class="row">
                    <div>
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" value="<?= isset($name) ? htmlspecialchars($name) : '' ?>" required>
                    </div>
                    <div>
                        <label for="gender">Gender *</label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?= (isset($gender) && $gender==='Male')?'selected':'' ?>>Male</option>
                            <option value="Female" <?= (isset($gender) && $gender==='Female')?'selected':'' ?>>Female</option>
                            <option value="Other" <?= (isset($gender) && $gender==='Other')?'selected':'' ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div>
                        <label for="mother_name">Mother's Name</label>
                        <input type="text" id="mother_name" name="mother_name" value="<?= isset($mother_name) ? htmlspecialchars($mother_name) : '' ?>">
                    </div>
                    <div>
                        <label for="father_name">Father's Name</label>
                        <input type="text" id="father_name" name="father_name" value="<?= isset($father_name) ? htmlspecialchars($father_name) : '' ?>">
                    </div>
                </div>

                <div class="row">
                    <div>
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" value="<?= isset($dob) ? htmlspecialchars($dob) : '' ?>">
                    </div>
                    <div>
                        <label for="blood_group">Blood Group</label>
                        <input type="text" id="blood_group" name="blood_group" value="<?= isset($blood_group) ? htmlspecialchars($blood_group) : '' ?>">
                    </div>
                </div>

                <div class="row">
                    <div>
                        <label for="religion">Religion</label>
                        <select id="religion" name="religion">
                            <option value="">Select Religion</option>
                            <option value="Islam" <?= (isset($religion) && $religion==='Islam')?'selected':'' ?>>Islam</option>
                            <option value="Hinduism" <?= (isset($religion) && $religion==='Hinduism')?'selected':'' ?>>Hinduism</option>
                            <option value="Christianity" <?= (isset($religion) && $religion==='Christianity')?'selected':'' ?>>Christianity</option>
                            <option value="Buddhism" <?= (isset($religion) && $religion==='Buddhism')?'selected':'' ?>>Buddhism</option>
                            <option value="Other" <?= (isset($religion) && $religion==='Other')?'selected':'' ?>>Other</option>
                        </select>
                    </div>
                    <div>
                        <label for="nationality">Nationality</label>
                        <input type="text" id="nationality" name="nationality" value="<?= isset($nationality) ? htmlspecialchars($nationality) : '' ?>">
                    </div>
                </div>

                <div class="row">
                    <div>
                        <label for="nid">NID</label>
                        <input type="text" id="nid" name="nid" value="<?= isset($nid) ? htmlspecialchars($nid) : '' ?>">
                    </div>
                    <div>
                        <label for="designation">Designation *</label>
                        <select id="designation" name="designation" required>
                            <option value="">Select Designation</option>
                            <option value="Office Assistant (Permanent)" <?= (isset($designation) && $designation==='Office Assistant (Permanent)')?'selected':'' ?>>Office Assistant (Permanent)</option>
                            <option value="Aaya (Permanent)" <?= (isset($designation) && $designation==='Aaya (Permanent)')?'selected':'' ?>>Aaya (Permanent)</option>
                            <option value="Driver (Temporary)" <?= (isset($designation) && $designation==='Driver (Temporary)')?'selected':'' ?>>Driver (Temporary)</option>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Address</legend>
                <label for="present_address">Present Address</label>
                <textarea id="present_address" name="present_address"><?= isset($present_address) ? htmlspecialchars($present_address) : '' ?></textarea>

                <label for="permanent_address">Permanent Address</label>
                <textarea id="permanent_address" name="permanent_address"><?= isset($permanent_address) ? htmlspecialchars($permanent_address) : '' ?></textarea>
            </fieldset>

            <fieldset>
                <legend>Professional</legend>
                <label for="education">Education Details</label>
                <textarea id="education" name="education"><?= isset($education) ? htmlspecialchars($education) : '' ?></textarea>

                <label for="experience">Experience</label>
                <textarea id="experience" name="experience"><?= isset($experience) ? htmlspecialchars($experience) : '' ?></textarea>
            </fieldset>

            <fieldset>
                <legend>Contact</legend>
                <label for="phone">Phone *</label>
                <input type="text" id="phone" name="phone" value="<?= isset($phone) ? htmlspecialchars($phone) : '' ?>" required>
            </fieldset>

            <fieldset>
                <legend>Photo</legend>
                <label for="photo">Upload Photo</label>
                <input type="file" id="photo" name="photo" accept="image/*">
            </fieldset>

            <fieldset>
                <legend>Documents</legend>
                <label>Upload Documents (PDF, DOC, JPG, PNG - max 2MB each)</label>
                <input type="file" id="documents" name="documents[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif" multiple>
                <small>You can select multiple files. Allowed formats: PDF, DOC, DOCX, JPG, PNG, GIF</small>
            </fieldset>

            <button type="submit" class="generatebutton">Add Staff</button>
        </form>
    </div>

</body>

</html>










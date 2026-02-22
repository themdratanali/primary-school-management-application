<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit;
}

$teacher_id = (int)$_SESSION['teacher_id'];
$message = '';
$message_type = 'success';

// Load current teacher
$stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$teacher) {
    die('Teacher record not found.');
}

// Parse education data for form
$teacherEducation = json_decode($teacher['education'] ?? '[]', true);
if (empty($teacherEducation)) {
    $teacherEducation = [['education' => '', 'institute' => '', 'result' => '']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $mother_name = trim($_POST['mother_name'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $blood_group = trim($_POST['blood_group'] ?? '');
    $religion = trim($_POST['religion'] ?? '');
    $nationality = trim($_POST['nationality'] ?? '');
    $nid = trim($_POST['nid'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $present_address = trim($_POST['present_address'] ?? '');
    $permanent_address = trim($_POST['permanent_address'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    
    // Education fields
    $education = $_POST['education'] ?? [];
    $institute = $_POST['institute'] ?? [];
    $result = $_POST['result'] ?? [];
    
    // Build education data
    $educationData = [];
    if (!empty($education) && is_array($education)) {
        for ($i = 0; $i < count($education); $i++) {
            if (!empty($education[$i])) {
                $educationData[] = [
                    'education' => $education[$i],
                    'institute' => isset($institute[$i]) ? $institute[$i] : '',
                    'result' => isset($result[$i]) ? $result[$i] : ''
                ];
            }
        }
    }
    $educationJson = json_encode($educationData, JSON_UNESCAPED_UNICODE);

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($name === '' || $phone === '' || $email === '') {
        $message = 'Please fill in all required fields.';
        $message_type = 'error';
    } else {
        // Handle photo upload
        $photo = $teacher['photo'];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
                $uploadDirFs = __DIR__ . '/../uploads/teachers/';
                if (!is_dir($uploadDirFs)) {
                    mkdir($uploadDirFs, 0777, true);
                }
                $filename = uniqid('teacher_', true) . '.' . $ext;
                $targetFsPath = $uploadDirFs . $filename;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFsPath)) {
                    $photo = '../uploads/teachers/' . $filename;
                }
            }
        }

        // Password change
        $hashed_password = null;
        if ($new_password !== '') {
            if ($current_password === '') {
                $message = 'Please enter your current password to change password.';
                $message_type = 'error';
            } elseif (empty($teacher['login_password']) || !password_verify($current_password, $teacher['login_password'])) {
                $message = 'Current password is incorrect.';
                $message_type = 'error';
            } elseif ($new_password !== $confirm_password) {
                $message = 'New password and confirm password do not match.';
                $message_type = 'error';
            } elseif (strlen($new_password) < 6) {
                $message = 'Password must be at least 6 characters.';
                $message_type = 'error';
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            }
        }

        if ($message_type !== 'error') {
            // Ensure email is unique among teachers
            $check = $conn->prepare("SELECT id FROM teachers WHERE email = ? AND id != ?");
            $check->bind_param("si", $email, $teacher_id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $message = 'Email is already used by another teacher.';
                $message_type = 'error';
            }
            $check->close();
        }

        if ($message_type !== 'error') {
            if ($hashed_password !== null) {
                $update = $conn->prepare("UPDATE teachers SET name=?, designation=?, mother_name=?, father_name=?, gender=?, dob=?, blood_group=?, religion=?, nationality=?, nid=?, phone=?, email=?, present_address=?, permanent_address=?, education=?, experience=?, photo=?, login_password=?, plain_password=? WHERE id=?");
                $update->bind_param("sssssssssssssssssssi", $name, $designation, $mother_name, $father_name, $gender, $dob, $blood_group, $religion, $nationality, $nid, $phone, $email, $present_address, $permanent_address, $educationJson, $experience, $photo, $hashed_password, $new_password, $teacher_id);
            } else {
                $update = $conn->prepare("UPDATE teachers SET name=?, designation=?, mother_name=?, father_name=?, gender=?, dob=?, blood_group=?, religion=?, nationality=?, nid=?, phone=?, email=?, present_address=?, permanent_address=?, education=?, experience=?, photo=? WHERE id=?");
                $update->bind_param("sssssssssssssssssi", $name, $designation, $mother_name, $father_name, $gender, $dob, $blood_group, $religion, $nationality, $nid, $phone, $email, $present_address, $permanent_address, $educationJson, $experience, $photo, $teacher_id);
            }

            if ($update->execute()) {
                $_SESSION['teacher_name'] = $name;
                $_SESSION['teacher_email'] = $email;
                $_SESSION['teacher_photo'] = $photo;
                $message = 'Profile updated successfully!';
                $message_type = 'success';

                // Reload updated teacher data
                $stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ? LIMIT 1");
                $stmt->bind_param("i", $teacher_id);
                $stmt->execute();
                $teacher = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                // Re-parse education data
                $teacherEducation = json_decode($teacher['education'] ?? '[]', true);
                if (empty($teacherEducation)) {
                    $teacherEducation = [['education' => '', 'institute' => '', 'result' => '']];
                }
            } else {
                $message = 'Error updating profile: ' . $update->error;
                $message_type = 'error';
            }
            $update->close();
        }
    }
}

// Resolve photo URL for display
$photoUrl = '../assets/img/default-avatar.jpg';
if (!empty($teacher['photo'])) {
    $candidates = [
        $teacher['photo'],
        '../' . ltrim($teacher['photo'], '/'),
        '../uploads/teachers/' . basename($teacher['photo']),
    ];
    foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
            $photoUrl = $candidate;
            break;
        }
    }
}
?>

<style>
    :root {
        --primary-color: #177a03;
        --primary-dark: #145a02;
        --secondary-color: #2c3e50;
        --text-color: #333333;
        --text-muted: #6c757d;
        --bg-light: #f5f7fa;
        --card-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
        --border-radius: 12px;
        --border-color: #d1d5db;
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: var(--bg-light);
        padding: 20px;
        margin: 0;
    }

    .profile-container {
        margin: 0 auto;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .page-title {
        color: var(--secondary-color);
        font-weight: 700;
        font-size: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0;
    }

    .page-title i {
        color: var(--primary-color);
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--text-muted);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }

    .back-btn:hover {
        color: var(--primary-color);
    }

    .message {
        padding: 14px 20px;
        border-radius: var(--border-radius);
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
        font-size: 14px;
    }

    .message.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .message.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .profile-form {
        background: #ffffff;
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
        overflow: hidden;
    }

    .form-section {
        padding: 25px 30px;
        border-bottom: 1px solid #e5e7eb;
    }

    .form-section:last-child {
        border-bottom: none;
    }

    .section-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--secondary-color);
        margin: 0 0 20px 0;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--primary-color);
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    .section-title i {
        color: var(--primary-color);
    }

    .photo-section {
        text-align: center;
        padding: 30px;
        background: linear-gradient(135deg, var(--primary-color) 0%, #1a9b06 100%);
        color: white;
    }

    .profile-photo {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        margin-bottom: 15px;
        display: inline-block;
    }

    .photo-actions {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }

    .file-input-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: white;
        color: var(--primary-color);
        padding: 10px 22px;
        border-radius: 25px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.2s;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
    }

    .file-input-label:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.25);
    }

    .photo-hint {
        font-size: 12px;
        opacity: 0.85;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 18px;
    }

    .form-group {
        margin-bottom: 0;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-group label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
        color: #374151;
        font-weight: 600;
        font-size: 13px;
    }

    .form-group label i {
        color: var(--primary-color);
        width: 16px;
    }

    .form-group label .required {
        color: #dc3545;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 14px;
        font-family: 'Segoe UI', sans-serif;
        transition: border-color 0.2s, box-shadow 0.2s;
        background: white;
        box-sizing: border-box;
    }

    .form-group textarea {
        min-height: 80px;
        resize: vertical;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(23, 122, 3, 0.1);
    }

    .form-group input::placeholder,
    .form-group textarea::placeholder {
        color: #9ca3af;
    }

    .education-entry {
        background: #f8f9fa;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        position: relative;
    }

    .education-entry .remove-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #dc3545;
        color: white;
        border: none;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }

    .education-entry h5 {
        margin: 0 0 12px 0;
        font-size: 13px;
        color: var(--text-muted);
        text-transform: uppercase;
    }

    .add-education-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--primary-color);
        color: white;
        padding: 10px 18px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: background 0.2s;
    }

    .add-education-btn:hover {
        background: var(--primary-dark);
    }

    .password-section {
        background: #fff3cd;
        padding: 20px;
        border-radius: 8px;
        margin-top: 10px;
    }

    .password-section .section-title {
        border-bottom-color: #ffc107;
    }

    .submit-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: var(--primary-color);
        color: white;
        padding: 14px 30px;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s;
        width: 100%;
        margin-top: 20px;
        box-shadow: 0 4px 15px rgba(23, 122, 3, 0.35);
    }

    .submit-btn:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(23, 122, 3, 0.4);
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .photo-section {
            padding: 20px;
        }
        
        .form-section {
            padding: 20px;
        }
        
        .page-header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
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

<div class="profile-container">
    <?php if ($message): ?>
        <div class="message <?= $message_type ?>">
            <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="profile-form">
        <!-- Photo Section -->
        <div class="photo-section">
            <img src="<?= htmlspecialchars($photoUrl) ?>" 
                 class="profile-photo" alt="Profile Photo">
            <div class="photo-actions">
                <label class="file-input-label">
                    <i class="fas fa-camera"></i> Change Photo
                    <input type="file" name="photo" accept="image/*" id="photoInput">
                </label>
                <p class="photo-hint">
                    <i class="fas fa-info-circle"></i>
                    Supported formats: JPG, JPEG, PNG, GIF
                </p>
            </div>
        </div>

        <!-- Personal Information -->
        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-user"></i> Personal Information
            </h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">
                        <i class="fas fa-user"></i> Full Name <span class="required">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($teacher['name'] ?? '') ?>" placeholder="Enter full name" required>
                </div>

                <div class="form-group">
                    <label for="designation">
                        <i class="fas fa-briefcase"></i> Designation
                    </label>
                    <input type="text" id="designation" name="designation" value="<?= htmlspecialchars($teacher['designation'] ?? '') ?>" placeholder="e.g., Senior Teacher">
                </div>

                <div class="form-group">
                    <label for="gender">
                        <i class="fas fa-venus-mars"></i> Gender <span class="required">*</span>
                    </label>
                    <select id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?= ($teacher['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($teacher['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= ($teacher['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="dob">
                        <i class="fas fa-calendar"></i> Date of Birth
                    </label>
                    <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($teacher['dob'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="blood_group">
                        <i class="fas fa-tint"></i> Blood Group
                    </label>
                    <select id="blood_group" name="blood_group">
                        <option value="">Select Blood Group</option>
                        <option value="A+" <?= ($teacher['blood_group'] ?? '') === 'A+' ? 'selected' : '' ?>>A+</option>
                        <option value="A-" <?= ($teacher['blood_group'] ?? '') === 'A-' ? 'selected' : '' ?>>A-</option>
                        <option value="B+" <?= ($teacher['blood_group'] ?? '') === 'B+' ? 'selected' : '' ?>>B+</option>
                        <option value="B-" <?= ($teacher['blood_group'] ?? '') === 'B-' ? 'selected' : '' ?>>B-</option>
                        <option value="AB+" <?= ($teacher['blood_group'] ?? '') === 'AB+' ? 'selected' : '' ?>>AB+</option>
                        <option value="AB-" <?= ($teacher['blood_group'] ?? '') === 'AB-' ? 'selected' : '' ?>>AB-</option>
                        <option value="O+" <?= ($teacher['blood_group'] ?? '') === 'O+' ? 'selected' : '' ?>>O+</option>
                        <option value="O-" <?= ($teacher['blood_group'] ?? '') === 'O-' ? 'selected' : '' ?>>O-</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="religion">
                        <i class="fas fa-pray"></i> Religion
                    </label>
                    <input type="text" id="religion" name="religion" value="<?= htmlspecialchars($teacher['religion'] ?? '') ?>" placeholder="e.g., Islam, Hindu, Christian">
                </div>

                <div class="form-group">
                    <label for="nationality">
                        <i class="fas fa-flag"></i> Nationality
                    </label>
                    <input type="text" id="nationality" name="nationality" value="<?= htmlspecialchars($teacher['nationality'] ?? '') ?>" placeholder="e.g., Bangladeshi">
                </div>

                <div class="form-group">
                    <label for="nid">
                        <i class="fas fa-id-card"></i> NID Number
                    </label>
                    <input type="text" id="nid" name="nid" value="<?= htmlspecialchars($teacher['nid'] ?? '') ?>" placeholder="National ID Number">
                </div>
            </div>
        </div>

        <!-- Family Information -->
        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-users"></i> Family Information
            </h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="father_name">
                        <i class="fas fa-male"></i> Father's Name
                    </label>
                    <input type="text" id="father_name" name="father_name" value="<?= htmlspecialchars($teacher['father_name'] ?? '') ?>" placeholder="Enter father's name">
                </div>

                <div class="form-group">
                    <label for="mother_name">
                        <i class="fas fa-female"></i> Mother's Name
                    </label>
                    <input type="text" id="mother_name" name="mother_name" value="<?= htmlspecialchars($teacher['mother_name'] ?? '') ?>" placeholder="Enter mother's name">
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-phone"></i> Contact Information
            </h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="phone">
                        <i class="fas fa-phone"></i> Phone Number <span class="required">*</span>
                    </label>
                    <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($teacher['phone'] ?? '') ?>" placeholder="Enter phone number" required>
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address <span class="required">*</span>
                    </label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($teacher['email'] ?? '') ?>" placeholder="Enter email address" required>
                </div>

                <div class="form-group full-width">
                    <label for="present_address">
                        <i class="fas fa-map-marker-alt"></i> Present Address
                    </label>
                    <textarea id="present_address" name="present_address" placeholder="Enter present address"><?= htmlspecialchars($teacher['present_address'] ?? '') ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label for="permanent_address">
                        <i class="fas fa-map-marked-alt"></i> Permanent Address
                    </label>
                    <textarea id="permanent_address" name="permanent_address" placeholder="Enter permanent address"><?= htmlspecialchars($teacher['permanent_address'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Education -->
        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-graduation-cap"></i> Education
            </h3>
            <div id="educationContainer">
                <?php foreach ($teacherEducation as $index => $edu): ?>
                    <div class="education-entry">
                        <?php if (count($teacherEducation) > 1): ?>
                            <button type="button" class="remove-btn" onclick="removeEducation(this)">
                                <i class="fas fa-times"></i>
                            </button>
                        <?php endif; ?>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-graduation-cap"></i> Degree/Certificate
                                </label>
                                <input type="text" name="education[]" value="<?= htmlspecialchars($edu['education'] ?? '') ?>" placeholder="e.g., BSc in Physics">
                            </div>
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-university"></i> Institute/University
                                </label>
                                <input type="text" name="institute[]" value="<?= htmlspecialchars($edu['institute'] ?? '') ?>" placeholder="e.g., University of Dhaka">
                            </div>
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-star"></i> Result/Grade
                                </label>
                                <input type="text" name="result[]" value="<?= htmlspecialchars($edu['result'] ?? '') ?>" placeholder="e.g., CGPA 3.80">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="add-education-btn" onclick="addEducation()">
                <i class="fas fa-plus"></i> Add Education
            </button>
        </div>

        <!-- Professional Information -->
        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-briefcase"></i> Professional Experience
            </h3>
            <div class="form-group full-width">
                <label for="experience">
                    <i class="fas fa-history"></i> Work Experience
                </label>
                <textarea id="experience" name="experience" placeholder="Describe your teaching experience, previous jobs, etc."><?= htmlspecialchars($teacher['experience'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Password Change -->
        <div class="form-section">
            <div class="password-section">
                <h3 class="section-title">
                    <i class="fas fa-lock"></i> Change Password
                </h3>
                <p style="margin: 0 0 15px 0; font-size: 13px; color: #856404;">
                    <i class="fas fa-info-circle"></i> Leave password fields empty if you don't want to change your password.
                </p>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="current_password">
                            <i class="fas fa-key"></i> Current Password
                        </label>
                        <input type="password" id="current_password" name="current_password" placeholder="Enter current password">
                    </div>

                    <div class="form-group">
                        <label for="new_password">
                            <i class="fas fa-lock-open"></i> New Password
                        </label>
                        <input type="password" id="new_password" name="new_password" placeholder="Enter new password">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i> Confirm Password
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <button type="submit" class="submit-btn">
                <i class="fas fa-save"></i>
                Update Profile
            </button>
        </div>
    </form>
</div>

<script>
    document.getElementById('photoInput').addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(event) {
                document.querySelector('.profile-photo').src = event.target.result;
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    });

    function addEducation() {
        const container = document.getElementById('educationContainer');
        const entry = document.createElement('div');
        entry.className = 'education-entry';
        entry.innerHTML = `
            <button type="button" class="remove-btn" onclick="removeEducation(this)">
                <i class="fas fa-times"></i>
            </button>
            <div class="form-grid">
                <div class="form-group">
                    <label>
                        <i class="fas fa-graduation-cap"></i> Degree/Certificate
                    </label>
                    <input type="text" name="education[]" value="" placeholder="e.g., BSc in Physics">
                </div>
                <div class="form-group">
                    <label>
                        <i class="fas fa-university"></i> Institute/University
                    </label>
                    <input type="text" name="institute[]" value="" placeholder="e.g., University of Dhaka">
                </div>
                <div class="form-group">
                    <label>
                        <i class="fas fa-star"></i> Result/Grade
                    </label>
                    <input type="text" name="result[]" value="" placeholder="e.g., CGPA 3.80">
                </div>
            </div>
        `;
        container.appendChild(entry);
    }

    function removeEducation(btn) {
        const container = document.getElementById('educationContainer');
        if (container.children.length > 1) {
            btn.parentElement.remove();
        }
    }
</script>

<script>
    function confirmLogout() {
        var result = confirm('Are you sure you want to logout?');
        if (result) {
            window.location.href = 'logout.php';
        }
    }
</script>

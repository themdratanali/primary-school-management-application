<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$admin_id = $_SESSION['admin_id'];
$message = '';
$error = '';

// Get current admin data
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if (!$admin) {
    die("Admin not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $number = trim($_POST['number'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($number)) {
        $error = "Please fill in all required fields.";
    } else {
        $photo = $admin['photo'];

        // Handle photo upload
        if (!empty($_FILES['photo']['name'])) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

            if (in_array($file_ext, $allowed_ext)) {
                if ($_FILES['photo']['size'] <= 2 * 1024 * 1024) {
                    if (!is_dir('../uploads/admin')) {
                        mkdir('../uploads/admin', 0777, true);
                    }
                    $photo = 'uploads/admin/' . uniqid('admin_', true) . '.' . $file_ext;
                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], '../' . $photo)) {
                        $error = "Failed to upload photo.";
                    }
                } else {
                    $error = "Photo must be under 2MB.";
                }
            } else {
                $error = "Invalid photo format. Allowed: jpg, jpeg, png, gif.";
            }
        }

        // Handle password change
        if (empty($error) && !empty($new_password)) {
            if (empty($current_password)) {
                $error = "Please enter your current password to change password.";
            } elseif (!password_verify($current_password, $admin['password'])) {
                $error = "Current password is incorrect.";
            } elseif ($new_password !== $confirm_password) {
                $error = "New password and confirm password do not match.";
            } elseif (strlen($new_password) < 6) {
                $error = "Password must be at least 6 characters.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            }
        }

        if (empty($error)) {
            // Check if email is already used by another admin
            $check_stmt = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
            $check_stmt->bind_param("si", $email, $admin_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $error = "Email is already used by another admin.";
            }
            $check_stmt->close();
        }

        if (empty($error)) {
            // Update admin profile
            if (isset($hashed_password)) {
                $update_stmt = $conn->prepare("UPDATE admins SET name=?, email=?, number=?, photo=?, password=? WHERE id=?");
                $update_stmt->bind_param("sssssi", $name, $email, $number, $photo, $hashed_password, $admin_id);
            } else {
                $update_stmt = $conn->prepare("UPDATE admins SET name=?, email=?, number=?, photo=? WHERE id=?");
                $update_stmt->bind_param("ssssi", $name, $email, $number, $photo, $admin_id);
            }

            if ($update_stmt->execute()) {
                // Update session variables
                $_SESSION['admin_name'] = $name;
                $_SESSION['admin_email'] = $email;
                $_SESSION['admin_number'] = $number;
                $_SESSION['admin_photo'] = $photo;

                $message = "Profile updated successfully!";
                $admin = array_merge($admin, [
                    'name' => $name,
                    'email' => $email,
                    'number' => $number,
                    'photo' => $photo
                ]);
            } else {
                $error = "Failed to update profile: " . $conn->error;
            }
            $update_stmt->close();
        }
    }
}

$photo_url = !empty($admin['photo']) && file_exists('../' . $admin['photo']) ? '../' . $admin['photo'] : '../assets/img/logo.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Admin Profile - Apex Model School</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/apex/assets/fontawesome/fontawesome-free-6.4.0-web/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            display: block;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            resize: vertical;
        }
        .photo-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            display: block;
            margin: 0 auto 20px;
            border: 3px solid #177a03;
        }
        .btn {
            background: #177a03;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #145a02;
        }
        .password-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .password-section h5 {
            margin-bottom: 15px;
            color: #333;
        }
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-user-edit"></i> Edit Admin Profile</h2>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                <br><small>Refreshing page to show updated photo...</small>
            </div>
            <script>
                // Refresh parent page after profile update to show new photo
                setTimeout(function() {
                    if (window.parent) {
                        window.parent.location.reload();
                    }
                }, 1500);
            </script>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <img src="<?= htmlspecialchars($photo_url) ?>" alt="Profile Photo" class="photo-preview">
        
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="photo">Change Photo</label>
                <input type="file" id="photo" name="photo" accept="image/*">
            </div>
            
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($admin['name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="number">Phone Number *</label>
                <input type="text" id="number" name="number" value="<?= htmlspecialchars($admin['number']) ?>" required>
            </div>
            
            <div class="password-section">
                <h5><i class="fas fa-lock"></i> Change Password (Optional)</h5>
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password">
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
            </div>
            
            <button type="submit" class="btn" style="margin-top: 20px;">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </form>
    </div>
</body>
</html>

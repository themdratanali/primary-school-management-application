<?php

require_once __DIR__ . '/../../env/session.php';
require_once __DIR__ . '/../../env/csrf.php';
include '../../env/config.php';

// Redirect if already logged in
if (isset($_SESSION['student_email'])) {
    ams_redirect(ams_student_url('dashboard'));
    exit;
}

// Handle login POST
if (isset($_POST['login'])) {
    ams_csrf_verify_post();
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Invalid email or password.";
    } else {
        $stmt = $conn->prepare("
            SELECT su.*, s.id AS main_student_id
            FROM student_users su
            LEFT JOIN students s ON s.id = su.student_id
            WHERE su.email = ?
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['student_email'] = $email;
                    
                    // Store main student ID from students table (for results queries)
                    $mainStudentId = isset($user['main_student_id']) ? (int)$user['main_student_id'] : 0;
                    if ($mainStudentId > 0) {
                        $_SESSION['student_main_id'] = $mainStudentId;
                    }

                    // Dynamic student id from legacy Student_{Batch}_{Class} tables
                    $dynamicId = isset($user['student_id']) ? (int)$user['student_id'] : 0;
                    $foundTable = '';

                    if ($dynamicId > 0) {
                        // Try to locate the matching Student_{Batch}_{Class} table
                        $tablesRes = $conn->query("SHOW TABLES LIKE 'Student\_%'");
                        if ($tablesRes) {
                            while ($tblRow = $tablesRes->fetch_row()) {
                                $tableName = $tblRow[0] ?? '';
                                if ($tableName === '') {
                                    continue;
                                }
                                $checkStmt = $conn->prepare("SELECT name, roll, batch_id, class_id FROM `$tableName` WHERE id = ? LIMIT 1");
                                if (!$checkStmt) {
                                    continue;
                                }
                                $checkStmt->bind_param("i", $dynamicId);
                                $checkStmt->execute();
                                $res = $checkStmt->get_result();
                                if ($res && $res->num_rows === 1) {
                                    $stuRow = $res->fetch_assoc();
                                    $foundTable = $tableName;
                                    // Store key info in session for dashboard/fees/etc.
                                    $_SESSION['student_dynamic_id'] = $dynamicId;
                                    $_SESSION['student_table'] = $tableName;
                                    $_SESSION['student_name'] = $stuRow['name'] ?? '';
                                    $_SESSION['student_roll'] = $stuRow['roll'] ?? '';
                                    $_SESSION['student_batch_id'] = isset($stuRow['batch_id']) ? (int)$stuRow['batch_id'] : null;
                                    $_SESSION['student_class_id'] = isset($stuRow['class_id']) ? (int)$stuRow['class_id'] : null;
                                    $checkStmt->close();
                                    break;
                                }
                                $checkStmt->close();
                            }
                        }
                    }

                    ams_redirect(ams_student_url('dashboard'));
                    exit;
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
            }

            $stmt->close();
        } else {
            $error = "Login failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <title>Student Login - Apex Model School</title>
    <link rel="shortcut icon" type="image/jpg" href="<?php echo BASE_URL; ?>/uploads/images/এ্যাপেক্স মডেল স্কুল.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/auth.css">
</head>
<body>
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-badge">
                <i class="fas fa-user-graduate"></i>
                <span>Student Portal</span>
            </div>
        </div>

        <?php if (isset($error) && $error): ?>
            <div class="auth-error">
                <span class="auth-error-icon"><i class="fas fa-exclamation-circle"></i></span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" class="auth-form" onsubmit="return validateForm()">
            <?= ams_csrf_field() ?>
            <div class="auth-field">
                <label class="auth-label" for="email">Email address</label>
                <div class="auth-input-wrapper">
                    <span class="auth-input-icon"><i class="fas fa-envelope"></i></span>
                    <input class="auth-input" type="email" id="email" name="email" placeholder="student@example.com" required autofocus>
                </div>
                <small id="emailError" class="text-danger" style="display:none;"></small>
            </div>

            <div class="auth-field">
                <label class="auth-label" for="password">Password</label>
                <div class="auth-input-wrapper">
                    <span class="auth-input-icon"><i class="fas fa-lock"></i></span>
                    <input class="auth-input" type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>

            <button type="submit" name="login" class="auth-submit">
                <i class="fas fa-sign-in-alt"></i>
                <span>Sign in</span>
            </button>
        </form>
    </div>

        <script>
        function validateForm() {
            var email = document.getElementById('email').value.trim();
            var password = document.getElementById('password').value;
            var emailError = document.getElementById('emailError');
            
            // Email validation
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                emailError.textContent = 'Please enter a valid email address';
                emailError.style.display = 'block';
                return false;
            }
            
            // Password validation - minimum 6 characters
            if (password.length < 6) {
                alert('Password must be at least 6 characters');
                return false;
            }
            
            emailError.style.display = 'none';
            return true;
        }
        </script>
    </div>
</body>
</html>












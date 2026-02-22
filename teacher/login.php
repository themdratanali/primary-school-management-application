<?php

$AMS_SESSION_LIFETIME = 86400;
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
include '../config/config.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['teacher_id'])) {
    header('Location: dashboard');
    exit;
}

if (isset($_POST['login'])) {
    ams_csrf_verify_post();
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM teachers WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $teacher = $result->fetch_assoc();
        if (!empty($teacher['login_password']) && password_verify($password, $teacher['login_password'])) {
            session_regenerate_id(true);
            
            // Clear any existing admin session
            unset($_SESSION['admin']);
            unset($_SESSION['admin_id']);
            unset($_SESSION['admin_name']);
            unset($_SESSION['admin_email']);
            unset($_SESSION['admin_number']);
            unset($_SESSION['admin_photo']);
            unset($_SESSION['admin_username']);
            
            // Set teacher session
            $_SESSION['teacher_id'] = $teacher['id'];
            $_SESSION['teacher_name'] = $teacher['name'];
            $_SESSION['teacher_email'] = $teacher['email'];
            $_SESSION['teacher_photo'] = $teacher['photo'] ?? '';
            $_SESSION['last_activity'] = time();

            // Optional: persistent helper cookie (secure/httponly)
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
            setcookie('teacher_logged_in', (string)$teacher['id'], [
                'expires' => time() + 86400,
                'path' => '/',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            header('Location: dashboard');
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <title>Teacher Login - Apex Model School</title>
    <link rel="shortcut icon" type="image/jpg" href="../assets/img/এ্যাপেক্স মডেল স্কুল.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/apex/assets/fontawesome/fontawesome-free-6.4.0-web/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-badge">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Teacher Portal</span>
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
                <label class="auth-label" for="email">Email</label>
                <div class="auth-input-wrapper">
                    <span class="auth-input-icon"><i class="fas fa-envelope"></i></span>
                    <input class="auth-input" type="email" id="email" name="email" placeholder="teacher@example.com" required autofocus>
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

<?php

require_once __DIR__ . '/../../env/session.php';
require_once __DIR__ . '/../../env/csrf.php';
require_once __DIR__ . '/../../env/config.php';

// Redirect if already logged in
if (isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('dashboard'));
    exit;
}

$error = '';

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ams_csrf_verify_post();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, name, email, number, photo FROM admins WHERE username = ? LIMIT 1");
        
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();

if (password_verify($password, $admin['password'])) {
                     session_regenerate_id(true);

                     $_SESSION['admin'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['name'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_number'] = $admin['number'];
                    $_SESSION['admin_photo'] = $admin['photo'];
                    $_SESSION['admin_username'] = $admin['username'];

                    ams_redirect(ams_admin_url('dashboard'));
                    exit;
                }
            }

            $stmt->close();
        }

        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login - Apex Model School</title>
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
                <i class="fas fa-user-shield"></i>
                <span>Admin Panel</span>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="auth-error">
                <span class="auth-error-icon"><i class="fas fa-exclamation-circle"></i></span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" class="auth-form" onsubmit="return validateForm()">
            <?= ams_csrf_field() ?>
            <div class="auth-field">
                <label class="auth-label" for="username">Username</label>
                <div class="auth-input-wrapper">
                    <span class="auth-input-icon"><i class="fas fa-user"></i></span>
                    <input class="auth-input" type="text" id="username" name="username" placeholder="Enter admin username" required autofocus>
                </div>
            </div>

            <div class="auth-field">
                <label class="auth-label" for="password">Password</label>
                <div class="auth-input-wrapper">
                    <span class="auth-input-icon"><i class="fas fa-lock"></i></span>
                    <input class="auth-input" type="password" id="password" name="password" placeholder="Enter password" required>
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
            var username = document.getElementById('username').value.trim();
            var password = document.getElementById('password').value;
            
            if (username.length < 3) {
                alert('Username must be at least 3 characters');
                return false;
            }
            
            if (password.length < 6) {
                alert('Password must be at least 6 characters');
                return false;
            }
            
            return true;
        }
        </script>
    </div>
</body>
</html>











<?php
include '../../env/session.php';
include '../../env/config.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $params['path'] ?? '/',
        'secure' => (bool)($params['secure'] ?? false),
        'httponly' => (bool)($params['httponly'] ?? true),
        'samesite' => 'Lax',
    ]);
}

session_destroy();

ams_redirect(ams_admin_url('login'));
exit;







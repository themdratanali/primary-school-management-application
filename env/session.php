<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    $lifetime = 0;
    if (isset($AMS_SESSION_LIFETIME) && is_int($AMS_SESSION_LIFETIME) && $AMS_SESSION_LIFETIME >= 0) {
        $lifetime = $AMS_SESSION_LIFETIME;
    }

    if ($lifetime > 0) {
        ini_set('session.gc_maxlifetime', (string)$lifetime);
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    }

    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}


<?php

require_once __DIR__ . '/session.php';

if (!function_exists('ams_csrf_token')) {
    function ams_csrf_token(): string {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('ams_csrf_is_valid')) {
    function ams_csrf_is_valid($token): bool {
        return is_string($token)
            && isset($_SESSION['csrf_token'])
            && is_string($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('ams_csrf_field')) {
    function ams_csrf_field(): string {
        $t = htmlspecialchars(ams_csrf_token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_token" value="' . $t . '">';
    }
}

if (!function_exists('ams_csrf_verify_post')) {
    function ams_csrf_verify_post(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return;
        }
        $token = $_POST['_token'] ?? '';
        if (!ams_csrf_is_valid($token)) {
            http_response_code(403);
            die('Invalid request token.');
        }
    }
}

if (!function_exists('ams_csrf_verify_post_json')) {
    function ams_csrf_verify_post_json(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return;
        }
        $token = $_POST['_token'] ?? '';
        if (!ams_csrf_is_valid($token)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid request token.']);
            exit;
        }
    }
}

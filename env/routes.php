<?php

if (!defined('BASE_URL')) {
    define('BASE_URL', '/apex');
}

define('PROJECT_ROOT', dirname(__DIR__));
define('UPLOADS_DIR', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'uploads');

define('ADMIN_ROUTE_PREFIX', 'author/a/admin');
define('STUDENT_ROUTE_PREFIX', 'public/s/student');

if (!function_exists('ams_url')) {
    function ams_url(string $path = ''): string
    {
        $base = rtrim(BASE_URL, '/');
        $path = trim($path, '/');

        return $path === '' ? $base . '/' : $base . '/' . $path;
    }
}

if (!function_exists('ams_public_url')) {
    function ams_public_url(string $page = ''): string
    {
        $page = trim($page, '/');

        if ($page === '' || $page === 'index' || $page === 'home') {
            return ams_url();
        }

        return ams_url($page);
    }
}

if (!function_exists('ams_admin_url')) {
    function ams_admin_url(string $page = 'dashboard'): string
    {
        $page = trim($page, '/');

        if ($page === '' || $page === 'dashboard') {
            return ams_url(ADMIN_ROUTE_PREFIX . '/dashboard');
        }

        return ams_url(ADMIN_ROUTE_PREFIX . '/' . $page);
    }
}


if (!function_exists('ams_student_page_file')) {
    function ams_student_page_file(string $page): string
    {
        static $aliases = [
            'results' => 'student_results',
            'fees' => 'student_fees',
            'logout' => 'student_logout',
        ];

        $page = trim($page, '/');

        return $aliases[$page] ?? $page;
    }
}

if (!function_exists('ams_student_url')) {
    function ams_student_url(string $page = 'dashboard'): string
    {
        $page = trim($page, '/');

        if ($page === '' || $page === 'dashboard') {
            return ams_url(STUDENT_ROUTE_PREFIX . '/dashboard');
        }

        $aliases = [
            'results' => 'results',
            'fees' => 'fees',
            'logout' => 'logout',
        ];

        $segment = $aliases[$page] ?? $page;

        return ams_url(STUDENT_ROUTE_PREFIX . '/' . $segment);
    }
}

if (!function_exists('ams_redirect')) {
    function ams_redirect(string $url, int $code = 302): void
    {
        header('Location: ' . $url, true, $code);
        exit;
    }
}

if (!function_exists('ams_upload_dir')) {
    function ams_upload_dir(string $subdir = ''): string
    {
        $path = UPLOADS_DIR;

        if ($subdir !== '') {
            $path .= DIRECTORY_SEPARATOR . trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $subdir), DIRECTORY_SEPARATOR);
        }

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        return $path . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('ams_upload_url')) {
    function ams_upload_url(string $subdir = '', string $file = ''): string
    {
        $url = rtrim(BASE_URL, '/') . '/uploads';

        if ($subdir !== '') {
            $url .= '/' . trim($subdir, '/');
        }

        if ($file !== '') {
            $url .= '/' . ltrim($file, '/');
        }

        return $url;
    }
}

<?php

if (!function_exists('ams_validate_student_table')) {
    function ams_validate_student_table(mysqli $conn, string $table): bool
    {
        if (!preg_match('/^Student_[a-zA-Z0-9_-]+$/', $table)) {
            return false;
        }

        $result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
        $exists = $result && $result->num_rows > 0;
        $result->close();

        return $exists;
    }
}

if (!function_exists('ams_validate_fee_category')) {
    function ams_validate_fee_category(string $category, array $allowed): bool
    {
        return array_key_exists($category, $allowed);
    }
}

if (!function_exists('ams_validate_fee_option')) {
    function ams_validate_fee_option(string $value, array $options): bool
    {
        return in_array($value, $options, true);
    }
}

if (!function_exists('ams_sanitize_table_part')) {
    function ams_sanitize_table_part(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9]/', '_', trim($value));
    }
}

if (!function_exists('ams_safe_filename')) {
    function ams_safe_filename(string $original): string
    {
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $base = pathinfo($original, PATHINFO_FILENAME);
        $base = preg_replace('/[^a-zA-Z0-9_-]/', '_', $base);

        if ($base === '') {
            $base = 'file';
        }

        return date('YmdHis') . '_' . $base . ($ext !== '' ? '.' . $ext : '');
    }
}

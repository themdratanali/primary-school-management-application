<?php
require_once __DIR__ . '/../../env/session.php';
include '../../env/config.php';

// Auth check - allow admin and student
if (!isset($_SESSION['admin']) && !isset($_SESSION['student_email'])) {
    http_response_code(403);
    die('Access denied');
}

// Get file path from query parameter
$path = $_GET['path'] ?? '';

if (empty($path)) {
    http_response_code(400);
    die('File path required');
}

// Resolve and validate file path
$fullPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
if (!file_exists($fullPath) || !is_file($fullPath)) {
    http_response_code(404);
    die('File not found');
}

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$mimeTypes = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif'
];

$mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
$fileName = basename($fullPath);

header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($fullPath));
readfile($fullPath);
exit;
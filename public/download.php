<?php

include '../env/config.php';

if (!isset($_GET['id']) || !isset($_GET['type'])) {
    http_response_code(400);
    exit('Invalid request');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if ($id === false || $id === null || $id <= 0) {
    http_response_code(400);
    exit('Invalid ID');
}

$allowed_types = ['routine', 'syllabus', 'exam_results'];
if (!in_array($type, $allowed_types, true)) {
    http_response_code(400);
    exit('Invalid type');
}

$table_map = [
    'routine' => ['table' => 'routine_files', 'dir' => 'routine/'],
    'syllabus' => ['table' => 'syllabus_files', 'dir' => 'syllabus/'],
    'exam_results' => ['table' => 'exam_results_files', 'dir' => 'exam_results/']
];

$table = $table_map[$type]['table'];
$upload_dir = $table_map[$type]['dir'];

$stmt = $conn->prepare("SELECT filename FROM {$table} WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    exit('File not found');
}

$row = $result->fetch_assoc();
$filename = $row['filename'];

$stmt->close();

$file_path = UPLOADS_DIR . DIRECTORY_SEPARATOR . $upload_dir . basename($filename);

if (!file_exists($file_path) || !is_file($file_path)) {
    http_response_code(404);
    exit('File does not exist');
}

$safe_filename = basename($filename);
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $safe_filename . '"');
header('Content-Length: ' . filesize($file_path));
header('Pragma: no-cache');
header('Expires: 0');
header('Cache-Control: no-cache, no-store, must-revalidate');

readfile($file_path);
exit;


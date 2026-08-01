<?php
require_once __DIR__ . '/../../env/session.php';
require_once __DIR__ . '/../../env/config.php';
require_once __DIR__ . '/../../env/csrf.php';

header('Content-Type: application/json');

// Auth check
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Verify CSRF token
ams_csrf_verify_post_json();

// Get and sanitize input
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$code = isset($_POST['code']) ? trim($_POST['code']) : '';
$total_mark = isset($_POST['total_mark']) ? intval($_POST['total_mark']) : 0;

if ($id <= 0 || $name === '' || $code === '' || $total_mark <= 0) {
    echo json_encode(['success' => false, 'message' => 'All fields are required and must be valid.']);
    exit;
}

$stmt = $conn->prepare("SELECT class_id FROM subjects WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($class_id);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Subject not found.']);
    $stmt->close();
    exit;
}
$stmt->close();

$check = $conn->prepare("SELECT id FROM subjects WHERE name = ? AND class_id = ? AND id != ?");
$check->bind_param("sii", $name, $class_id, $id);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Another subject with this name exists in the same class.']);
    $check->close();
    exit;
}
$check->close();

$stmt = $conn->prepare("UPDATE subjects SET name = ?, code = ?, total_mark = ? WHERE id = ?");
$stmt->bind_param("ssii", $name, $code, $total_mark, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Subject updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}
$stmt->close();





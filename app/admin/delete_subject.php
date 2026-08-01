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

// Get and validate subject ID
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid subject ID.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Subject deleted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}
$stmt->close();





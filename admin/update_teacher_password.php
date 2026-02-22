<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

$teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (!$teacher_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid teacher ID']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
    exit;
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE teachers SET login_password = ?, plain_password = ? WHERE id = ?");
$stmt->bind_param("ssi", $hashed_password, $password, $teacher_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();

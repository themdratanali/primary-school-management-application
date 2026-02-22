<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

if (empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Password cannot be empty']);
    exit;
}

$stmt = $conn->prepare("UPDATE student_users SET password = ? WHERE id = ?");
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt->bind_param("si", $hashed_password, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update password']);
}

$stmt->close();
$conn->close();

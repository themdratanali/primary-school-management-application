<?php
include '../config/config.php';

if (!isset($_POST['type']) || !isset($_POST['title']) || !isset($_POST['message'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$type = $_POST['type'];
$title = $_POST['title'];
$message = $_POST['message'];
$created_by = isset($_POST['created_by']) ? intval($_POST['created_by']) : null;

$stmt = $conn->prepare("INSERT INTO notifications (type, title, message, created_by) VALUES (?, ?, ?, ?)");
$stmt->bind_param("sssi", $type, $title, $message, $created_by);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'notification_id' => $conn->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();

<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

$result = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT $limit");

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Get unread count
$unread_count = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0")->fetch_assoc()['count'];

echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'unread_count' => $unread_count
]);

<?php
require_once __DIR__ . '/../../env/session.php';
include '../../env/config.php';

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
$conn->set_charset("utf8mb4");

$batch_id = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

if ($batch_id && $class_id) {
    $result = $conn->query("SELECT id, name, roll FROM students WHERE batch_id = $batch_id AND class_id = $class_id ORDER BY roll ASC");
    if ($result) {
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        echo json_encode(['success' => true, 'students' => $students]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
}
exit;
?>





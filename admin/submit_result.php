<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_POST['submit'])) {
    header('Location: manage_results.php');
    exit;
}

$student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
$batch_id = isset($_POST['batch_id']) ? intval($_POST['batch_id']) : 0;
$class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
$marks = $_POST['marks'] ?? [];

if (!$student_id || !$batch_id || !$class_id || empty($marks)) {
    echo "Missing required data.";
    exit;
}

$stmt_check = $conn->prepare("SELECT id FROM results WHERE student_id = ? AND batch_id = ? AND class_id = ? AND subject_id = ?");
$stmt_insert = $conn->prepare("INSERT INTO results (student_id, batch_id, class_id, subject_id, marks) VALUES (?, ?, ?, ?, ?)");
$stmt_update = $conn->prepare("UPDATE results SET marks = ? WHERE id = ?");

foreach ($marks as $subject_id => $mark) {
    $subject_id = intval($subject_id);
    $mark = intval($mark);

    $stmt_check->bind_param("iiii", $student_id, $batch_id, $class_id, $subject_id);
    $stmt_check->execute();
    $res = $stmt_check->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $result_id = $row['id'];
        $stmt_update->bind_param("ii", $mark, $result_id);
        $stmt_update->execute();
    } else {
        $stmt_insert->bind_param("iiiis", $student_id, $batch_id, $class_id, $subject_id, $mark);
        $stmt_insert->execute();
    }
}

echo "<p style='color:green;'>Results saved successfully!</p>";
echo '<p><a href="manage_results.php">Add more results</a></p>';
echo '<p><a href="dashboard.php">Back to Dashboard</a></p>';

<?php
require_once __DIR__ . '/../../env/session.php';
require_once __DIR__ . '/../../env/csrf.php';
include '../../env/config.php';

if (!isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

if (!isset($_POST['submit'])) {
    ams_redirect(ams_admin_url('manage_results'));
    exit;
}

ams_csrf_verify_post();

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
echo '<p><a href="' . ams_admin_url('manage_results') . '">Add more results</a></p>';
echo '<p><a href="' . ams_admin_url('dashboard') . '">Back to Dashboard</a></p>';









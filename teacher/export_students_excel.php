<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['teacher_id']) && !isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$conn->set_charset("utf8mb4");

$batch_id = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

if (!$batch_id || !$class_id) {
    die('Invalid parameters.');
}

// Fetch batch and class names
$batch_name = '';
$class_name = '';

$stmtB = $conn->prepare("SELECT name FROM batches WHERE id = ?");
if ($stmtB) {
    $stmtB->bind_param("i", $batch_id);
    $stmtB->execute();
    $stmtB->bind_result($batch_name);
    $stmtB->fetch();
    $stmtB->close();
}

$stmtC = $conn->prepare("SELECT name FROM classes WHERE id = ?");
if ($stmtC) {
    $stmtC->bind_param("i", $class_id);
    $stmtC->execute();
    $stmtC->bind_result($class_name);
    $stmtC->fetch();
    $stmtC->close();
}

// Resolve actual student table (dynamic Student_Batch_Class or fallback to students)
$student_table = 'students';
if ($batch_name && $class_name) {
    $batch_slug = preg_replace('/\s+/', '', $batch_name);
    $class_slug = preg_replace('/\s+/', '', $class_name);
    $possible_table = "Student_{$batch_slug}_{$class_slug}";
    $check_table = $conn->query("SHOW TABLES LIKE '$possible_table'");
    if ($check_table && $check_table->num_rows > 0) {
        $student_table = $possible_table;
    }
}

// Load all columns so "all student all data" is exported
$sql = "SELECT * FROM `$student_table` WHERE batch_id = ? AND class_id = ? ORDER BY roll ASC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Database error.');
}

$stmt->bind_param("ii", $batch_id, $class_id);
$stmt->execute();
$result = $stmt->get_result();

$csv_data = [];
$headers = [];

if ($result && $result->num_rows > 0) {
    // Use first row to build header from column names
    $firstRow = $result->fetch_assoc();
    $headers = array_keys($firstRow);
    $csv_data[] = $headers;
    $csv_data[] = array_values($firstRow);

    while ($row = $result->fetch_assoc()) {
        $csv_data[] = array_values($row);
    }
} else {
    // No data, still output header from students table structure
    $columnsRes = $conn->query("SHOW COLUMNS FROM `$student_table`");
    if ($columnsRes) {
        while ($col = $columnsRes->fetch_assoc()) {
            $headers[] = $col['Field'];
        }
    }
    if (!empty($headers)) {
        $csv_data[] = $headers;
    }
}

$stmt->close();

$csv_string = '';
foreach ($csv_data as $row) {
    $escaped = array_map(function ($value) {
        $value = (string)$value;
        $value = str_replace('"', '""', $value);
        return $value;
    }, $row);
    $csv_string .= '"' . implode('","', $escaped) . '"' . "\r\n";
}

$safe_batch = $batch_name ? preg_replace('/\s+/', '_', $batch_name) : 'Batch_' . $batch_id;
$safe_class = $class_name ? preg_replace('/\s+/', '_', $class_name) : 'Class_' . $class_id;

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Students_' . $safe_batch . '_' . $safe_class . '_' . date('Y-m-d_H-i-s') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

echo $csv_string;
exit;


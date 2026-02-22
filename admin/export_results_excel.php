<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$conn->set_charset("utf8mb4");

// Helper to keep table name format consistent with marksheet pages
function sanitize_table_part_export($str)
{
    return preg_replace('/[^a-zA-Z0-9]/', '_', trim($str));
}

$batch_id = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$result_type = isset($_GET['result_type']) ? trim($_GET['result_type']) : '';

if (!$batch_id || !$class_id || $result_type === '') {
    die('Missing parameters.');
}

// Resolve batch year from batch name (same logic as get_batch_year.php)
$batch_name = '';
$stmtB = $conn->prepare("SELECT name FROM batches WHERE id = ?");
if ($stmtB) {
    $stmtB->bind_param("i", $batch_id);
    $stmtB->execute();
    $stmtB->bind_result($batch_name);
    $stmtB->fetch();
    $stmtB->close();
}

if (!$batch_name) {
    die('Batch not found.');
}

if (!preg_match('/\d{4}/', $batch_name, $matches)) {
    die('Batch year not found in batch name.');
}
$batch_year = $matches[0];

// Resolve class name
$class_name = '';
$stmtC = $conn->prepare("SELECT name FROM classes WHERE id = ?");
if ($stmtC) {
    $stmtC->bind_param("i", $class_id);
    $stmtC->execute();
    $stmtC->bind_result($class_name);
    $stmtC->fetch();
    $stmtC->close();
}

if (!$class_name) {
    die('Class not found.');
}

// Use same naming logic as marksheet / marksheet_bulk
$batch_clean = sanitize_table_part_export($batch_name);
$class_clean = sanitize_table_part_export($class_name);
$result_type_clean = strtolower(str_replace(' ', '_', $result_type));

$results_table = "results_{$batch_year}_{$class_clean}_{$result_type_clean}";
$student_table = "Student_{$batch_clean}_{$class_clean}";

// Ensure results table exists
$checkTable = $conn->query("SHOW TABLES LIKE '$results_table'");
if (!$checkTable || $checkTable->num_rows === 0) {
    die('No results table found for selected Batch, Class, and Result Type.');
}

// Get subjects for this class (columns)
$subjectsRes = $conn->prepare("SELECT id, name FROM subjects WHERE class_id = ? ORDER BY id ASC");
if (!$subjectsRes) {
    die('Error loading subjects.');
}
$subjectsRes->bind_param("i", $class_id);
$subjectsRes->execute();
$subjects = $subjectsRes->get_result();

if (!$subjects || $subjects->num_rows === 0) {
    die('No subjects found for this class.');
}

$subjectIds = [];
$subjectNames = [];
while ($row = $subjects->fetch_assoc()) {
    $subjectIds[] = (int)$row['id'];
    $subjectNames[] = $row['name'];
}
$subjectsRes->close();

// Load all students from the dynamic Student_{batch}_{class} table
$checkStudentTable = $conn->query("SHOW TABLES LIKE '$student_table'");
if (!$checkStudentTable || $checkStudentTable->num_rows === 0) {
    die('No student table found for selected Batch and Class.');
}

$studentsRes = $conn->query("SELECT id, name FROM `$student_table` ORDER BY name ASC");
if (!$studentsRes) {
    die('Error loading students.');
}

$students = [];
while ($row = $studentsRes->fetch_assoc()) {
    $students[(int)$row['id']] = $row['name'];
}

if (empty($students)) {
    die('No students found for selected Batch and Class.');
}

// Load marks for this result set from the dynamic results_{year}_{class}_{exam} table
$marksRes = $conn->query("SELECT student_id, subject_id, marks FROM `$results_table`");
if (!$marksRes) {
    die('Error loading marks.');
}

$marks = [];
while ($row = $marksRes->fetch_assoc()) {
    $sId = (int)$row['student_id'];
    $subId = (int)$row['subject_id'];
    if (!isset($marks[$sId])) {
        $marks[$sId] = [];
    }
    $marks[$sId][$subId] = $row['marks'];
}

// Build CSV: Name - Subj1 - Subj2 - ...
$csv_data = [];
$header = array_merge(['Name'], $subjectNames);
$csv_data[] = $header;

foreach ($students as $studentId => $studentName) {
    $row = [$studentName];
    foreach ($subjectIds as $subId) {
        $row[] = isset($marks[$studentId][$subId]) ? $marks[$studentId][$subId] : '';
    }
    $csv_data[] = $row;
}

$csv_string = '';
foreach ($csv_data as $row) {
    $escaped = array_map(function ($value) {
        $value = (string)$value;
        $value = str_replace('"', '""', $value);
        return $value;
    }, $row);
    $csv_string .= '"' . implode('","', $escaped) . '"' . "\r\n";
}

$safe_batch = preg_replace('/\s+/', '_', $batch_name);
$safe_class = preg_replace('/\s+/', '_', $class_name);
$safe_type = preg_replace('/\s+/', '_', $result_type_clean);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Results_' . $safe_batch . '_' . $safe_class . '_' . $safe_type . '_' . date('Y-m-d_H-i-s') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

echo $csv_string;
exit;


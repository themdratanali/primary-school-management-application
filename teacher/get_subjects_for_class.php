<?php
include '../config/config.php';
require_once __DIR__ . '/../includes/session.php';

if (!isset($_POST['class_id'], $_POST['student_id'], $_POST['batch_year'], $_POST['result_type'])) {
    echo 'Please select all required fields (Batch, Class, Student, and Result Type)';
    exit;
}

$class_id = intval($_POST['class_id']);
$student_id = intval($_POST['student_id']);
$batch_year = $_POST['batch_year'];
$result_type = trim($_POST['result_type']);

// Check if user is admin or teacher
$is_admin = isset($_SESSION['admin']);
$teacher_id = $_SESSION['teacher_id'] ?? 0;

$stmtC = $conn->prepare("SELECT name FROM classes WHERE id = ?");
$stmtC->bind_param("i", $class_id);
$stmtC->execute();
$resC = $stmtC->get_result();
$class_name_row = $resC->fetch_assoc();
$stmtC->close();

if (!$class_name_row || empty($class_name_row['name'])) {
    echo 'Invalid class selected';
    exit;
}

if (!preg_match('/^\d{4}$/', $batch_year)) {
    echo 'Invalid batch year';
    exit;
}

if (empty($result_type)) {
    echo 'Please select a Result Type';
    exit;
}

if (empty($student_id)) {
    echo 'Please select a Student';
    exit;
}

$class_slug = strtolower(str_replace(' ', '_', $class_name_row['name']));
$result_type_clean = strtolower(str_replace(' ', '_', $result_type));
$table_name = "results_{$batch_year}_{$class_slug}_{$result_type_clean}";

// Build subject query - filter by teacher if not admin
if ($is_admin) {
    // Admin sees all subjects
    $stmt = $conn->prepare("SELECT id, name, total_mark FROM subjects WHERE class_id = ? ORDER BY id ASC");
    $stmt->bind_param("i", $class_id);
} else {
    // Teacher sees only their assigned subjects
    $stmt = $conn->prepare("
        SELECT s.id, s.name, s.total_mark 
        FROM subjects s
        INNER JOIN teacher_subjects ts ON s.id = ts.subject_id
        WHERE s.class_id = ? AND ts.teacher_id = ?
        ORDER BY s.id ASC
    ");
    $stmt->bind_param("ii", $class_id, $teacher_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    if ($is_admin) {
        echo "No subjects found for this class.";
    } else {
        echo "No subjects assigned to you for this class.";
    }
    exit;
}

// Load existing marks if the results table exists
$marks = [];
$table_exists = $conn->query("SHOW TABLES LIKE '$table_name'")->num_rows > 0;
if ($table_exists) {
    $stmt2 = $conn->prepare("SELECT subject_id, marks FROM `$table_name` WHERE student_id = ? AND class_id = ? AND exam_type = ?");
    $stmt2->bind_param("iis", $student_id, $class_id, $result_type);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($row = $res2->fetch_assoc()) {
        $marks[$row['subject_id']] = $row['marks'];
    }
    $stmt2->close();
}

// Render a professional table with Max Marks column and appropriate input max
echo '<div class="results-table-wrapper">';
echo '<table>';
echo '<thead><tr><th>Subject</th><th style="width:110px; text-align:center;">Max Marks</th><th style="width:140px; text-align:center;">Marks Obtained</th></tr></thead><tbody>';

while ($row = $result->fetch_assoc()) {
    $subject_id = $row['id'];
    $subject_name = htmlspecialchars($row['name']);
    $max_mark = intval($row['total_mark']) ?: 100;
    $mark_val = isset($marks[$subject_id]) ? $marks[$subject_id] : '';
    echo '<tr>';
    echo '<td>' . $subject_name . '</td>';
    echo '<td style="text-align:center;">' . $max_mark . '</td>';
    echo '<td style="text-align:center;"><input class="mark-input" type="number" min="0" max="' . $max_mark . '" name="marks[' . $subject_id . ']" value="' . htmlspecialchars($mark_val) . '" required></td>';
    echo '</tr>';
}

echo '</tbody></table>';
echo '</div>';

$stmt->close();
?>

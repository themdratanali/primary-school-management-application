<?php
include '../config/config.php';

if (!isset($_POST['class_id'], $_POST['student_id'], $_POST['batch_year'], $_POST['result_type'])) {
    echo 'Please select all required fields';
    exit;
}

$class_id = intval($_POST['class_id']);
$student_id = intval($_POST['student_id']);
$batch_year = $_POST['batch_year'];
$result_type = trim($_POST['result_type']);

// Resolve class name to match table naming used by admin/manage_results.php
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

// Fetch subjects with their max marks if available
$stmt = $conn->prepare("SELECT id, name, total_mark FROM subjects WHERE class_id = ? ORDER BY id ASC");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "No subjects found for this class.";
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
echo '<table id="marksTable">';
echo '<thead><tr><th>Subject</th><th style="width:90px; text-align:center;">Max Marks</th><th style="width:110px; text-align:center;">Marks Obtained</th><th style="width:80px; text-align:center;">Grade</th><th style="width:80px; text-align:center;">Point</th></tr></thead><tbody>';

while ($row = $result->fetch_assoc()) {
    $subject_id = $row['id'];
    $subject_name = htmlspecialchars($row['name']);
    $max_mark = intval($row['total_mark']) ?: 100;
    $mark_val = isset($marks[$subject_id]) ? $marks[$subject_id] : '';
    echo '<tr>';
    echo '<td>' . $subject_name . '</td>';
    echo '<td style="text-align:center;">' . $max_mark . '</td>';
    echo '<td style="text-align:center;"><input class="mark-input" type="number" min="0" max="' . $max_mark . '" name="marks[' . $subject_id . ']" value="' . htmlspecialchars($mark_val) . '" data-max="' . $max_mark . '" oninput="calculateGrade(this)" required></td>';
    echo '<td style="text-align:center;" class="grade-cell"><span class="grade">-</span></td>';
    echo '<td style="text-align:center;" class="point-cell"><span class="point">-</span></td>';
    echo '</tr>';
}

echo '</tbody></table>';
echo '</div>';
?>
<script>
function calculateGrade(input) {
    var mark = parseFloat(input.value) || 0;
    var maxMark = parseFloat(input.dataset.max);
    var percentage = (mark / maxMark) * 100;
    var row = input.closest('tr');
    var gradeCell = row.querySelector('.grade');
    var pointCell = row.querySelector('.point');
    
    var grade, point;
    
    if (percentage >= 80) { grade = 'A+'; point = '5.00'; }
    else if (percentage >= 70) { grade = 'A'; point = '4.00'; }
    else if (percentage >= 60) { grade = 'A-'; point = '3.30'; }
    else if (percentage >= 50) { grade = 'B'; point = '3.00'; }
    else if (percentage >= 40) { grade = 'C'; point = '2.00'; }
    else if (percentage >= 33) { grade = 'D'; point = '1.00'; }
    else { grade = 'F'; point = '0.00'; }
    
    if (input.value === '') {
        gradeCell.textContent = '-';
        pointCell.textContent = '-';
    } else {
        gradeCell.textContent = grade;
        pointCell.textContent = point;
    }
}

// Auto-calculate for pre-filled marks
document.querySelectorAll('.mark-input').forEach(function(input) {
    if (input.value) calculateGrade(input);
});
</script>
<?php

$stmt->close();
?>

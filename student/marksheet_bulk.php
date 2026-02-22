<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

// Allow both admin and teacher to access bulk marksheet
if (!isset($_SESSION['admin']) && !isset($_SESSION['teacher_id'])) {
    header('Location: ../admin/login.php');
    exit;
}

$conn->set_charset("utf8mb4");

$batches = $conn->query("SELECT id, name FROM batches ORDER BY name");
$classes = $conn->query("SELECT id, name FROM classes ORDER BY name");

$batch_id = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$exam_type = isset($_GET['exam_type']) ? $_GET['exam_type'] : 'Annual Exam';

$exam_title = $exam_type;
$watermark_text = strtoupper($exam_title);

$students = [];
$batch_name = '';
$class_name = '';
$results_table = '';
$results_table_exists = false;

function sanitize_table_part_bulk($str)
{
    return preg_replace('/[^a-zA-Z0-9]/', '_', trim($str));
}

function bulk_calculateGrade($mark, $total_mark)
{
    $percentage = ($total_mark > 0) ? ($mark / $total_mark) * 100 : 0;

    if ($percentage >= 80) return ['grade' => 'A+', 'point' => 5.00];
    elseif ($percentage >= 70) return ['grade' => 'A', 'point' => 4.00];
    elseif ($percentage >= 60) return ['grade' => 'A-', 'point' => 3.30];
    elseif ($percentage >= 50) return ['grade' => 'B', 'point' => 3.00];
    elseif ($percentage >= 40) return ['grade' => 'C', 'point' => 2.00];
    elseif ($percentage >= 33) return ['grade' => 'D', 'point' => 1.00];
    else return ['grade' => 'F', 'point' => 0.00];
}

if ($batch_id && $class_id) {
    // Get batch and class names
    $stmt_batch = $conn->prepare("SELECT name FROM batches WHERE id = ?");
    $stmt_batch->bind_param("i", $batch_id);
    $stmt_batch->execute();
    $stmt_batch->bind_result($batch_name);
    $stmt_batch->fetch();
    $stmt_batch->close();

    $stmt_class = $conn->prepare("SELECT name FROM classes WHERE id = ?");
    $stmt_class->bind_param("i", $class_id);
    $stmt_class->execute();
    $stmt_class->bind_result($class_name);
    $stmt_class->fetch();
    $stmt_class->close();

    $batch_clean = sanitize_table_part_bulk($batch_name);
    $class_clean = sanitize_table_part_bulk($class_name);

    $student_table = "Student_" . $batch_clean . "_" . $class_clean;

    $table_exists = $conn->query("SHOW TABLES LIKE '$student_table'")->num_rows > 0;

    if ($table_exists) {
        $stmt = $conn->prepare("SELECT * FROM `$student_table` ORDER BY roll ASC");
        $stmt->execute();
        $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Determine year from batch name
        $year = date('Y');
        if (preg_match('/\d{4}/', $batch_name, $matches)) {
            $year = $matches[0];
        }

        $results_table = "results_" . $year . "_" . $class_clean . "_" . strtolower(str_replace(' ', '_', $exam_type));
        $results_table_exists = $conn->query("SHOW TABLES LIKE '$results_table'")->num_rows > 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>All Mark Sheet</title>
    <link rel="stylesheet" href="../assets/css/admit_card.css">
    <link rel="stylesheet" href="../assets/css/marksheet.css">
    <style>
        .bulk-container {
            max-width: 1100px;
            margin: 20px auto;
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.08);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .bulk-header {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            text-align: center;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
            justify-content: center;
            align-items: flex-end;
        }

        .filter-row label {
            display: block;
            font-size: 13px;
            margin-bottom: 4px;
            color: #333;
        }

        .filter-row select {
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 13px;
            min-width: 160px;
        }

        .filter-row button {
            padding: 9px 18px;
            border-radius: 6px;
            border: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-download {
            background: #177a03;
            color: #fff;
        }

        .btn-download:hover {
            background: #145a02;
        }

        .students-list {
            margin-top: 15px;
            max-height: 220px;
            overflow-y: auto;
            border: 1px solid #eee;
            border-radius: 6px;
        }

        .students-list table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .students-list th,
        .students-list td {
            padding: 8px 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        .students-list th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .no-students {
            text-align: center;
            color: #777;
            padding: 20px 10px;
            font-size: 13px;
        }

        .bulk-marksheet-wrapper {
            margin-top: 20px;
        }

        .marksheet-page {
            page-break-after: always;
        }
    </style>
</head>

<body>
    <div class="bulk-container">
        <div class="bulk-header">All Mark Sheet PDF</div>
        <form method="get" style="margin-bottom: 0;">
            <div class="filter-row">
                <div>
                    <label for="batch_id">Batch</label>
                    <select name="batch_id" id="batch_id" onchange="this.form.submit()">
                        <option value="0">-- Select Batch --</option>
                        <?php $batches->data_seek(0);
                        while ($b = $batches->fetch_assoc()): ?>
                            <option value="<?= $b['id'] ?>" <?= $batch_id === (int)$b['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label for="class_id">Class</label>
                    <select name="class_id" id="class_id" onchange="this.form.submit()">
                        <option value="0">-- Select Class --</option>
                        <?php $classes->data_seek(0);
                        while ($c = $classes->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>" <?= $class_id === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label for="exam_type">Exam Type</label>
                    <select name="exam_type" id="exam_type" onchange="this.form.submit()">
                        <option value="1st Tutorial" <?= $exam_type === '1st Tutorial' ? 'selected' : '' ?>>1st Tutorial</option>
                        <option value="2nd Tutorial" <?= $exam_type === '2nd Tutorial' ? 'selected' : '' ?>>2nd Tutorial</option>
                        <option value="3rd Tutorial" <?= $exam_type === '3rd Tutorial' ? 'selected' : '' ?>>3rd Tutorial</option>
                        <option value="1st Term Exam" <?= $exam_type === '1st Term Exam' ? 'selected' : '' ?>>1st Term Exam</option>
                        <option value="2nd Term Exam" <?= $exam_type === '2nd Term Exam' ? 'selected' : '' ?>>2nd Term Exam</option>
                        <option value="Annual Exam" <?= $exam_type === 'Annual Exam' ? 'selected' : '' ?>>Annual Exam</option>
                    </select>
                </div>

                <div>
                    <button type="button"
                            class="btn-download" style="margin-bottom: 17px;"
                            onclick="downloadBulkMarkSheets()"
                            <?= (empty($students) || !$results_table_exists) ? 'disabled' : '' ?>>
                        All Mark Sheet PDF
                    </button>
                </div>
                <div>
                    <button type="button"
                            class="btn-download"  style="margin-bottom: 17px;"
                            onclick="downloadBulkMarkSheetsCsv()"
                            <?= (empty($students) || !$results_table_exists) ? 'disabled' : '' ?>>
                        All Mark Sheet CVC
                    </button>
                </div>
            </div>
        </form>

        <div class="students-list">
            <?php if (!empty($students)): ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align:center;">Roll</th>
                            <th>Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $stu): ?>
                            <tr>
                                <td style="text-align:center;"><?= htmlspecialchars($stu['roll']) ?></td>
                                <td><?= htmlspecialchars($stu['name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-students">
                    Select Batch, Class and Exam Type to load students for bulk marksheet download.
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($students) && $results_table_exists): ?>
            <div class="bulk-marksheet-wrapper" id="bulkMarkSheets">
                <?php
                foreach ($students as $stu):
                    // Load results for this student
                    $results = $conn->query("
                        SELECT sub.code, sub.name, sub.total_mark, res.marks
                        FROM `$results_table` res
                        JOIN subjects sub ON res.subject_id = sub.id
                        WHERE res.student_id = " . (int)$stu['id'] . "
                        ORDER BY sub.name ASC
                    ");

                    $student_batch_name = $batch_name;
                    $student_class_name = $class_name;
                    ?>
                    <div class="admit-container marksheet-page">
                        <div class="admit-card" id="marksheet_<?= (int)$stu['id'] ?>" style="position: relative; padding: 20px;">
                            <div class="watermark"><?= htmlspecialchars($watermark_text) ?></div>
                            <div class="header" style="text-align:center;">
                                <img src="<?= htmlspecialchars($stu['photo'] ?? 'student_photo.png') ?>" alt="Student Photo" width="100" height="100" style="float:left; border-radius:8px;">
                                <div class="header-center">
                                    <h2>Apex Model School</h2>
                                    <p style="margin: 2px 0; font-size: 12px;">Kharkhari Bypass, Motihar, Paba, Rajshahi</p>
                                    <p style="margin: 3px 0; font-size: 23px;">Mark Sheet</p>
                                    <p style="margin: 5px auto; padding: 2px; font-size: 15px; border: 1px solid #000; width: fit-content; border-radius: 5px;"><?= htmlspecialchars($exam_title) ?></p>
                                </div>
                                <img src="../logo.png" alt="School Logo" width="80" height="80" style="float:right; border-radius:8px;">
                            </div>
                            <hr style="clear:both;">
                            <div class="row">
                                <p><strong>Name:</strong> <?= htmlspecialchars($stu['name']) ?></p>
                                <p><strong>Roll:</strong> <?= htmlspecialchars($stu['roll']) ?></p>
                            </div>
                            <div class="row">
                                <p><strong>Batch:</strong> <?= htmlspecialchars($student_batch_name) ?></p>
                                <p><strong>Class:</strong> <?= htmlspecialchars($student_class_name) ?></p>
                            </div>

                            <table>
                                <thead>
                                    <tr>
                                        <th>Sl.</th>
                                        <th>Subject Code</th>
                                        <th>Subject Name</th>
                                        <th>Total Mark</th>
                                        <th>Obtained Mark</th>
                                        <th>Grade</th>
                                        <th>Point</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sl = 1;
                                    $total_marks = 0;
                                    $total_obtained = 0;
                                    $total_points = 0;
                                    $subject_count = 0;

                                    if ($results && $results->num_rows > 0):
                                        while ($r = $results->fetch_assoc()):
                                            $grade_info = bulk_calculateGrade($r['marks'], $r['total_mark']);
                                            $total_marks += $r['total_mark'];
                                            $total_obtained += $r['marks'];
                                            $total_points += $grade_info['point'];
                                            $subject_count++;
                                            ?>
                                            <tr>
                                                <td><?= $sl++ ?></td>
                                                <td><?= htmlspecialchars($r['code']) ?></td>
                                                <td><?= htmlspecialchars($r['name']) ?></td>
                                                <td style="text-align:center;"><?= htmlspecialchars($r['total_mark']) ?></td>
                                                <td style="text-align:center;"><?= htmlspecialchars($r['marks']) ?></td>
                                                <td style="text-align:center;"><strong><?= $grade_info['grade'] ?></strong></td>
                                                <td style="text-align:center;"><?= number_format($grade_info['point'], 2) ?></td>
                                            </tr>
                                        <?php endwhile;
                                    else: ?>
                                        <tr>
                                            <td colspan="7" style="text-align:center; font-style:italic; color:#777;">
                                                No marks found for this student and exam type.
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if ($subject_count > 0): ?>
                                        <tr style="background: #f5f5f5; font-weight: bold;">
                                            <td colspan="3" style="text-align:right;">Total:</td>
                                            <td style="text-align:center;"><?= $total_marks ?></td>
                                            <td style="text-align:center;"><?= $total_obtained ?></td>
                                            <td colspan="2" style="text-align:center;">Average Point: <?= number_format($total_points / $subject_count, 2) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                            <div class="signature-section">
                                <div class="signature-box">Student Signature</div>
                                <div class="signature-box">Guardian Signature</div>
                                <div class="signature-box">Teacher Signature</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadBulkMarkSheets() {
            const container = document.getElementById('bulkMarkSheets');
            if (!container) {
                alert('No marksheets to download. Please select Batch, Class and Exam Type first.');
                return;
            }

            const clone = container.cloneNode(true);

            const opt = {
                margin: [10, 10, 10, 10],
                filename: 'marksheets_batch_<?= (int)$batch_id ?>_class_<?= (int)$class_id ?>_<?= preg_replace('/\s+/', '_', $exam_type) ?>.pdf',
                image: {type: 'png', quality: 1},
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    logging: false
                },
                jsPDF: {unit: 'mm', format: 'a4', orientation: 'portrait'}
            };

            html2pdf().set(opt).from(clone).save();
        }

        function downloadBulkMarkSheetsCsv() {
            const params = new URLSearchParams(window.location.search);
            const batchId = params.get('batch_id') || '0';
            const classId = params.get('class_id') || '0';
            const examType = params.get('exam_type') || '';

            if (batchId === '0' || classId === '0' || !examType) {
                alert('Please select Batch, Class and Exam Type first.');
                return;
            }

            const url = `../admin/export_results_excel.php?batch_id=${encodeURIComponent(batchId)}&class_id=${encodeURIComponent(classId)}&result_type=${encodeURIComponent(examType)}`;
            window.location.href = url;
        }
    </script>
</body>

</html>


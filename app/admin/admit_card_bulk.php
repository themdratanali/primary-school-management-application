<?php
require_once __DIR__ . '/../../env/session.php';
include '../../env/config.php';

if (!isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

$conn->set_charset("utf8mb4");

$batches = $conn->query("SELECT * FROM batches ORDER BY name");
$classes = $conn->query("SELECT * FROM classes ORDER BY name");

$batch_id = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$admit_type = isset($_GET['admit_type']) ? $_GET['admit_type'] : '1st Tutorial';

// Map admit type to display title and watermark (same as student/admit_card.php)
$admit_type_map = [
    '1st Tutorial' => ['1st Tutorial', '1ST TUTORIAL'],
    '2nd Tutorial' => ['2nd Tutorial', '2ND TUTORIAL'],
    '3rd Tutorial' => ['3rd Tutorial', '3RD TUTORIAL'],
    '1st Term Exam' => ['1st Term Exam', '1ST TERM EXAM'],
    '2nd Term Exam' => ['2nd Term Exam', '2ND TERM EXAM'],
    'Annual Exam' => ['Annual Exam', 'ANNUAL EXAM']
];

$exam_title = $admit_type_map[$admit_type][0] ?? $admit_type;
$watermark_text = $admit_type_map[$admit_type][1] ?? strtoupper($admit_type);

$students = [];
$subjects = [];

if ($batch_id > 0 && $class_id > 0) {
    // Resolve dynamic student table if exists
    $student_table = "students";
    $batch_name_res = $conn->query("SELECT name FROM batches WHERE id = $batch_id");
    $class_name_res = $conn->query("SELECT name FROM classes WHERE id = $class_id");
    if ($batch_name_res && $class_name_res) {
        $batch_name = strtolower(str_replace(' ', '_', $batch_name_res->fetch_assoc()['name']));
        $class_name = strtolower(str_replace(' ', '_', $class_name_res->fetch_assoc()['name']));
        $possible_table = "Student_{$batch_name}_{$class_name}";
        $check_table = $conn->query("SHOW TABLES LIKE '$possible_table'");
        if ($check_table->num_rows > 0) {
            $student_table = $possible_table;
        }
    }

    if ($conn->query("SHOW TABLES LIKE '$student_table'")->num_rows > 0) {
        $stmt = $conn->prepare("
            SELECT s.*, c.name AS class_name, b.name AS batch_name
            FROM `$student_table` s
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN batches b ON s.batch_id = b.id
            WHERE s.batch_id = ? AND s.class_id = ?
            ORDER BY s.roll ASC
        ");
        if ($stmt) {
            $stmt->bind_param("ii", $batch_id, $class_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $students[] = $row;
            }
            $stmt->close();
        }
    }

    // Load subjects once for this class
    $subStmt = $conn->prepare("SELECT code, name, total_mark FROM subjects WHERE class_id = ? ORDER BY name ASC");
    if ($subStmt) {
        $subStmt->bind_param("i", $class_id);
        $subStmt->execute();
        $subRes = $subStmt->get_result();
        while ($row = $subRes->fetch_assoc()) {
            $subjects[] = $row;
        }
        $subStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>All Student Admit Card</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/admit_card.css">
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

        .btn-primary {
            background: #177a03;
            color: #fff;
        }

        .btn-primary:hover {
            background: #145a02;
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

        .admit-card-page {
            page-break-after: always;
        }
    </style>
</head>

<body>
    <div class="bulk-container">
        <div class="bulk-header">All Student Admit Card</div>

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
                    <label for="admit_type">Admit Type</label>
                    <select name="admit_type" id="admit_type" onchange="this.form.submit()">
                        <option value="1st Tutorial" <?= $admit_type === '1st Tutorial' ? 'selected' : '' ?>>1st Tutorial</option>
                        <option value="2nd Tutorial" <?= $admit_type === '2nd Tutorial' ? 'selected' : '' ?>>2nd Tutorial</option>
                        <option value="3rd Tutorial" <?= $admit_type === '3rd Tutorial' ? 'selected' : '' ?>>3rd Tutorial</option>
                        <option value="1st Term Exam" <?= $admit_type === '1st Term Exam' ? 'selected' : '' ?>>1st Term Exam</option>
                        <option value="2nd Term Exam" <?= $admit_type === '2nd Term Exam' ? 'selected' : '' ?>>2nd Term Exam</option>
                        <option value="Annual Exam" <?= $admit_type === 'Annual Exam' ? 'selected' : '' ?>>Annual Exam</option>
                    </select>
                </div>

                <div>
                    <button type="button" class="btn-download" onclick="downloadBulkAdmit()" <?= empty($students) ? 'disabled' : '' ?>>
                        Download Admit Cards (PDF)
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
                    Select Batch and Class to load students for bulk admit card download.
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($students) && !empty($subjects)): ?>
            <div class="bulk-admit-wrapper" id="bulkAdmitCards">
                <?php
                $slTemplate = 1;
                foreach ($students as $student):
                    ?>
                    <div class="admit-container admit-card-page">
                        <div class="admit-card" style="position: relative; padding: 5px;">
                            <div class="watermark"><?= htmlspecialchars($watermark_text) ?></div>
                            <div class="watermark-logo"></div>
                            <div class="card-border">
                                <div class="header">
                                    <img src="<?php echo BASE_URL; ?>/uploads/images/logo.png" alt="School Logo" class="logo-img">
                                    <div class="header-center">
                                        <h2 class="school-name">Apex Model School</h2>
                                        <p class="school-address">Kharkhari Bypass, Motihar, Paba, Rajshahi</p>
                                        <p class="admit-title">Admit Card</p>
                                        <p class="exam-type"><?= htmlspecialchars($exam_title) ?></p>
                                    </div>
                                    <img src="<?php
                                        $photo = $student['photo'] ?? '';
                                        $photoPath = BASE_URL . '/uploads/students/default-photo.jpg';
                                        if (!empty($photo)) {
                                            $storedPath = $photo;
                                            $cleanPath = str_replace('../', '', $storedPath);
                                            $fsPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);
                                            if (file_exists($fsPath)) {
                                                $photoPath = BASE_URL . '/' . $cleanPath;
                                            }
                                        }
                                        echo htmlspecialchars($photoPath);
                                    ?>" alt="Student Photo" class="photo-img">
                                </div>
                                <hr>
                                <div class="row">
                                    <p><strong>Name: </strong> <?= htmlspecialchars($student['name']) ?></p>
                                    <p><strong>Roll: </strong> <?= htmlspecialchars($student['roll']) ?></p>
                                </div>
                                <div class="row">
                                    <p><strong>Batch: </strong> <?= htmlspecialchars($student['batch_name'] ?? '') ?></p>
                                    <p><strong>Class: </strong> <?= htmlspecialchars($student['class_name'] ?? '') ?></p>
                                </div>

                                <table>
                                    <thead>
                                        <tr>
                                            <th>Sl.</th>
                                            <th>Subject Code</th>
                                            <th>Subject Name</th>
                                            <th>Total Mark</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $sl = 1;
                                        foreach ($subjects as $sub): ?>
                                            <tr>
                                                <td style="width: 5%; font-size: 13px;"><?= $sl++ ?></td>
                                                <td style="width: 20%; font-size: 13px;"><?= htmlspecialchars($sub['code']) ?></td>
                                                <td style="width: 55%; font-size: 15px;"><?= htmlspecialchars($sub['name']) ?></td>
                                                <td style="width: 15%; font-size: 13px; text-align: center;"><?= htmlspecialchars($sub['total_mark']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <div class="signature-section">
                                    <div class="signature-left">
                                        <div class="instructions-title">INSTRUCTIONS FOR THE EXAMINEES</div>
                                        <ol class="instructions">
                                            <li>Bring your admit card and student ID to the exam hall.</li>
                                            <li>Arrive at least 30 minutes before the exam starts.</li>
                                            <li>Electronic devices are strictly prohibited during the exam.</li>
                                            <li>Follow all instructions given by the invigilator.</li>
                                            <li>Maintain silence and discipline inside the exam hall.</li>
                                            <li>Admit card must be produced when demanded by the invigilator.</li>
                                        </ol>
                                    </div>
                                    <div class="signature-right">
                                        <div class="signature-box">
                                            <p class="signature-line">(Signature)</p>
                                            <p style="font-weight: 600;font-size: 15px;color: #333;position: relative;z-index: 2;">Md. Milon Sarkar</p>
                                            <p style="font-weight: 550;font-size: 13px;color: #333;position: relative;z-index: 2;">Headmaster (Director)</p>
                                            <p style="font-weight: 530;font-size: 13px;color: #333;position: relative;z-index: 2;">Apex Model School</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadBulkAdmit() {
            const container = document.getElementById('bulkAdmitCards');
            if (!container) {
                alert('No admit cards to download. Please select Batch and Class first.');
                return;
            }

            const clone = container.cloneNode(true);

            const opt = {
                margin: [10, 10, 10, 10],
                filename: 'admit_cards_batch_<?= (int)$batch_id ?>_class_<?= (int)$class_id ?>.pdf',
                image: { type: 'png', quality: 1 },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    logging: false
                },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(clone).save();
        }
    </script>
</body>

</html>













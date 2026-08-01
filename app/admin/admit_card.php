<?php
require_once __DIR__ . '/../../env/session.php';
include '../../env/config.php';

if (!isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

$batches = $conn->query("SELECT * FROM batches ORDER BY name");
$classes = $conn->query("SELECT * FROM classes ORDER BY name");

$batch_id = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$admit_type = isset($_GET['admit_type']) ? $_GET['admit_type'] : '1st Tutorial';

// Map admit type to display title and watermark
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

$student_table = "students";
if ($batch_id > 0 && $class_id > 0) {
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
}

$students = [];
if ($conn->query("SHOW TABLES LIKE '$student_table'")->num_rows > 0) {
    $student_sql = "SELECT id, name FROM `$student_table`";
    $student_sql .= " ORDER BY name ASC";
    $student_result = $conn->query($student_sql);
    if ($student_result) {
        while ($row = $student_result->fetch_assoc()) {
            $students[] = $row;
        }
    }
}

$student = null;
$results = null;
if ($student_id > 0) {
    $stmt = $conn->prepare("
        SELECT s.*, c.name AS class_name, b.name AS batch_name
        FROM `$student_table` s
        LEFT JOIN classes c ON s.class_id = c.id
        LEFT JOIN batches b ON s.batch_id = b.id
        WHERE s.id = ?
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student_res = $stmt->get_result();
    if ($student_res->num_rows > 0) {
        $student = $student_res->fetch_assoc();
        $results = $conn->query("
            SELECT code, name, total_mark
            FROM subjects
            WHERE class_id = {$student['class_id']}
            ORDER BY name ASC
        ");
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create Admit Card</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/admit_card.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container-flex">
        <div class="form-container">
            <form method="get">
                <label>Batch:</label>
                <select name="batch_id" onchange="this.form.submit()">
                    <option value="0">-- All Batches --</option>
                    <?php while ($batch = $batches->fetch_assoc()): ?>
                        <option value="<?= $batch['id'] ?>" <?= $batch_id == $batch['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($batch['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label>Class:</label>
                <select name="class_id" onchange="this.form.submit()">
                    <option value="0">-- All Classes --</option>
                    <?php while ($class = $classes->fetch_assoc()): ?>
                        <option value="<?= $class['id'] ?>" <?= $class_id == $class['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($class['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label>Student:</label>
                <select name="student_id">
                    <option value="0">-- Select Student --</option>
                    <?php foreach ($students as $stu): ?>
                        <option value="<?= $stu['id'] ?>" <?= $student_id == $stu['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($stu['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Admit Type:</label>
                <select name="admit_type">
                    <option value="1st Tutorial" <?= $admit_type === '1st Tutorial' ? 'selected' : '' ?>>1st Tutorial</option>
                    <option value="2nd Tutorial" <?= $admit_type === '2nd Tutorial' ? 'selected' : '' ?>>2nd Tutorial</option>
                    <option value="3rd Tutorial" <?= $admit_type === '3rd Tutorial' ? 'selected' : '' ?>>3rd Tutorial</option>
                    <option value="1st Term Exam" <?= $admit_type === '1st Term Exam' ? 'selected' : '' ?>>1st Term Exam</option>
                    <option value="2nd Term Exam" <?= $admit_type === '2nd Term Exam' ? 'selected' : '' ?>>2nd Term Exam</option>
                    <option value="Annual Exam" <?= $admit_type === 'Annual Exam' ? 'selected' : '' ?>>Annual Exam</option>
                </select>

                <button class="generatebutton" type="submit" style="margin-top: 15px;">Generate Admit Card</button>
                <?php if ($student): ?>
                <button type="button" class="generatebutton" style="margin-top: 10px;" onclick="downloadAdmitCard()">
                    <i class="fas fa-download"></i> Download Admit Card
                </button>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($student): ?>
<div class="admit-container">
                <div id="admitCard">
                    <div class="watermark"><?= htmlspecialchars($watermark_text) ?></div>
                    <div class="watermark-logo"></div>
                    <div class="card-border">
                        <div class="header">
                            <img src="<?php echo BASE_URL; ?>/uploads/images/logo.png" alt="Apex Model School Logo" class="logo-img">
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
                        <p><strong>Batch: </strong> <?= htmlspecialchars($student['batch_name']) ?></p>
                        <p><strong>Class: </strong> <?= htmlspecialchars($student['class_name']) ?></p>
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
                            while ($r = $results->fetch_assoc()): ?>
                                <tr>
                                    <td style="width: 5%; font-size: 13px;"><?= $sl++ ?></td>
                                    <td style="width: 20%; font-size: 13px;"><?= htmlspecialchars($r['code']) ?></td>
                                    <td style="width: 55%; font-size: 15px;"><?= htmlspecialchars($r['name']) ?></td>
                                    <td style="width: 15%; font-size: 13px; text-align: center;"><?= htmlspecialchars($r['total_mark']) ?></td>
                                </tr>
                            <?php endwhile; ?>
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
        <?php endif; ?>
        </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadAdmitCard() {
            const element = document.getElementById('admitCard');
            const studentName = '<?= htmlspecialchars(str_replace(' ', '_', $student['name'])) ?>';
            
            // Create a working copy of the element
            const elementClone = element.cloneNode(true);
            
            const opt = {
                margin: [6, 6, 6, 6],
                filename: `admit_card_${studentName}.pdf`,
                image: {
                    type: 'png',
                    quality: 1
                },
                html2canvas: {
                    scale: 3,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    logging: false,
                    typeface: 'Arial'
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait',
                    compress: false,
                    hotfixes: ['px_scaling']
                }
            };
            
            try {
                // Remove the watermark and reapply after PDF generation
                const watermark = elementClone.querySelector('.watermark');
                const watermarkLogo = elementClone.querySelector('.watermark-logo');
                if (watermark) {
                    watermark.style.opacity = '0.05';
                }
                if (watermarkLogo) {
                    watermarkLogo.style.opacity = '0.06';
                }
                
                html2pdf()
                    .set(opt)
                    .from(elementClone)
                    .toPdf()
                    .get('pdf')
                    .then(pdf => {
                        pdf.save(opt.filename);
                    })
                    .catch(err => {
                        console.error('PDF generation error:', err);
                        alert('Unable to generate PDF. Please try again or check browser console for details.');
                    });
                    
            } catch (error) {
                console.error('PDF generation error:', error);
                alert('Error generating PDF. ' + error.message);
            }
        }
    </script>
</body>

</html>













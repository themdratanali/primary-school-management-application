<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

// Allow both admin and teacher to view student profile
if (!isset($_SESSION['admin']) && !isset($_SESSION['teacher_id'])) {
    header('Location: ../admin/login.php');
    exit;
}

$is_admin = isset($_SESSION['admin']);
$is_teacher = isset($_SESSION['teacher_id']);

$table = $_GET['table'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (!$table || !$id) {
    die("Invalid request.");
}

$checkTable = $conn->query("SHOW TABLES LIKE '$table'");
if ($checkTable->num_rows == 0) {
    die("Table does not exist.");
}

$stmt = $conn->prepare("SELECT * FROM `$table` WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Student not found.");
}

$student = $result->fetch_assoc();
$stmt->close();

$batch_name = '';
$class_name = '';

if (!empty($student['batch_id'])) {
    $stmtBatch = $conn->prepare("SELECT name FROM batches WHERE id = ?");
    $stmtBatch->bind_param("i", $student['batch_id']);
    $stmtBatch->execute();
    $resBatch = $stmtBatch->get_result();
    if ($resBatch->num_rows > 0) {
        $batch_name = $resBatch->fetch_assoc()['name'];
    }
    $stmtBatch->close();
}

if (!empty($student['class_id'])) {
    $stmtClass = $conn->prepare("SELECT name FROM classes WHERE id = ?");
    $stmtClass->bind_param("i", $student['class_id']);
    $stmtClass->execute();
    $resClass = $stmtClass->get_result();
    if ($resClass->num_rows > 0) {
        $class_name = $resClass->fetch_assoc()['name'];
    }
    $stmtClass->close();
}

$photo = '';
if (!empty($student['photo'])) {
    $photoPath = $student['photo'];
    if (file_exists($photoPath)) {
        $photo = $photoPath;
    } elseif (file_exists(substr($photoPath, 1))) {
        // Remove leading slash if present
        $photo = substr($photoPath, 1);
    } elseif (file_exists("../" . $photoPath)) {
        $photo = "../" . $photoPath;
    } elseif (file_exists("../uploads/students/" . basename($photoPath))) {
        $photo = "../uploads/students/" . basename($photoPath);
    } else {
        $photo = '../uploads/students/default-photo.jpg';
    }
} else {
    $photo = '../uploads/students/default-photo.jpg';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Student Profile - <?= htmlspecialchars($student['name']) ?> </title>
    <link rel="stylesheet" href="../assets/css/student_profile.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <script>
        function downloadProfile() {
            const element = document.getElementById('admitCard');
            const opt = {
                margin: [0, 12, 12, 12],
                filename: 'student_profile_<?= $id ?>.pdf',
                image: {
                    type: 'png',
                    quality: 1
                },
                html2canvas: {
                    scale: 3,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    scrollY: 0
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait',
                    compress: false,
                    hotfixes: ['px_scaling']
                },
                pagebreak: {
                    mode: ['avoid-all']
                }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</head>

<body>
    <div class="container-flex">
        <div class="admit-container">
            <div id="admitCard">
                <div class="watermark">
                    <img src="../logo.png" alt="Watermark">
                </div>
                <div class="card-border">
                    <div class="header">
                        <img src="<?= htmlspecialchars($photo) ?>" alt="Student Photo">
                        <div class="header-center">
                            <h2>Apex Model School</h2>
                            <p style="font-size: 12px;margin-top: 2px;">Kharkhari Bypass, Motihar, Paba, Rajshahi</p>
                        </div>
                        <img src="../logo.png" alt="School Logo">
                    </div>
                    <hr>

                    <div class="section-title">Personal Information</div>
                    <table class="info-table">
                        <tr>
                            <td><strong>Name : </strong> <?= htmlspecialchars($student['name']) ?></td>
                            <td><strong>Gender: </strong> <?= htmlspecialchars($student['gender']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Mother's Name : </strong> <?= htmlspecialchars($student['mother_name']) ?></td>
                            <td><strong>Father's Name : </strong> <?= htmlspecialchars($student['father_name']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Date of Birth : </strong> <?= htmlspecialchars($student['dob']) ?></td>
                            <td><strong>Birth Certificate No. : </strong> <?= htmlspecialchars($student['birth_cert_no']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Blood Group : </strong> <?= htmlspecialchars($student['blood_group']) ?></td>
                            <td><strong>Religion : </strong> <?= htmlspecialchars($student['religion']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Nationality : </strong> <?= htmlspecialchars($student['nationality']) ?></td>
                        </tr>
                    </table>

                    <div class="section-title">Address</div>
                    <table class="info-table">
                        <tr>
                            <td><strong>Present Address : </strong> <?= nl2br(htmlspecialchars($student['present_address'])) ?></td>
                            <td><strong>Permanent Address : </strong> <?= nl2br(htmlspecialchars($student['permanent_address'])) ?></td>
                        </tr>
                    </table>

                    <div class="section-title">Academic Information</div>
                    <table class="info-table">
                        <tr>
                            <td><strong>Batch :</strong> <?= htmlspecialchars($batch_name) ?></td>
                            <td><strong>Class:</strong> <?= htmlspecialchars($class_name) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Roll :</strong> <?= htmlspecialchars($student['roll']) ?></td>
                        </tr>
                    </table>

                    <div class="section-title">Contact Information</div>
                    <table class="info-table">
                        <tr>
                            <td><strong>Father Mobile : </strong> <?= htmlspecialchars($student['father_mobile']) ?></td>
                            <td><strong>Mother Mobile : </strong> <?= htmlspecialchars($student['mother_mobile']) ?></td>
                        </tr>
                    </table>

                    <div class="section-title">Local Guardian</div>
                    <table class="info-table">
                        <tr>
                            <td><strong>Guardian Name : </strong> <?= htmlspecialchars($student['guardian_name']) ?></td>
                            <td><strong>Guardian Profession : </strong> <?= htmlspecialchars($student['guardian_profession']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Guardian Mobile : </strong> <?= htmlspecialchars($student['guardian_mobile']) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php if ($is_admin): ?>
    <div style="text-align: center;">
        <button class="download-btn" onclick="downloadProfile()">Download Profile</button>
        <a href="student_profile_edit.php?table=<?= $table ?>&id=<?= $id ?>" class="download-btn" style="background: #28a745; margin-left: 10px;">Edit Profile</a>
    </div>
    <?php endif; ?>
</body>

</html>

<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Staff ID missing or invalid");
}

$id = (int)$_GET['id'];

$sql = "SELECT * FROM staff WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Staff not found");
}

// Get subjects if teacher
$subjects_display = '';
if (!empty($data['subject_id'])) {
    $subject_ids = explode(',', $data['subject_id']);
    $subject_ids = array_map('trim', $subject_ids);
    $placeholders = implode(',', array_fill(0, count($subject_ids), '?'));
    $types = str_repeat('i', count($subject_ids));
    $subject_sql = "SELECT subject_name FROM subjects WHERE id IN ($placeholders)";
    $subject_stmt = $conn->prepare($subject_sql);
    $subject_stmt->bind_param($types, ...$subject_ids);
    $subject_stmt->execute();
    $subject_result = $subject_stmt->get_result();
    $subjects = [];
    while ($subject = $subject_result->fetch_assoc()) {
        $subjects[] = $subject['subject_name'];
    }
    $subjects_display = implode(', ', $subjects);
}

$photo = '';
if (!empty($data['photo'])) {
    if (file_exists($data['photo'])) {
        $photo = $data['photo'];
    } elseif (file_exists("../" . $data['photo'])) {
        $photo = "../" . $data['photo'];
    } elseif (file_exists("../uploads/staff/" . basename($data['photo']))) {
        $photo = "../uploads/staff/" . basename($data['photo']);
    } else {
        $photo = '../uploads/teachers/default-photo.jpg';
    }
} else {
    $photo = '../uploads/teachers/default-photo.jpg';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Staff Profile - <?= htmlspecialchars($data['name']) ?></title>
    <link rel="stylesheet" href="../assets/css/teacher_profile.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadProfile() {
            const element = document.getElementById('admitCard');
            const opt = {
                margin: [0, 12, 12, 12],
                filename: 'staff_profile_<?= $id ?>.pdf',
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
                        <img src="<?= htmlspecialchars($photo) ?>" alt="Staff Photo">
                        <div class="header-center">
                            <h2>Apex Model School</h2>
                            <p style="font-size: 12px;margin-top: 2px;">Kharkhari Bypass, Motihar, Paba, Rajshashi</p>
                        </div>
                        <img src="../logo.png" alt="School Logo">
                    </div>
                    <hr>

                    <div class="section-title">Personal Information</div>
                    <table class="info-table">
                        <tr>
                            <td><strong>Name : </strong> <?= htmlspecialchars($data['name']) ?></td>
                            <td><strong>Gender : </strong> <?= htmlspecialchars($data['gender']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Mother's Name : </strong> <?= htmlspecialchars($data['mother_name']) ?></td>
                            <td><strong>Father's Name : </strong> <?= htmlspecialchars($data['father_name']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Date of Birth : </strong> <?= htmlspecialchars($data['dob']) ?></td>
                            <td><strong>Blood Group : </strong> <?= htmlspecialchars($data['blood_group']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Religion : </strong> <?= htmlspecialchars($data['religion']) ?></td>
                            <td><strong>Nationality : </strong> <?= htmlspecialchars($data['nationality']) ?></td>
                        </tr>
                    </table>

                    <div class="section-title">Professional Information</div>
                    <table class="info-table">
                        <tr>
                            <td><strong>Designation : </strong> <?= htmlspecialchars($data['designation'] ?? 'N/A') ?></td>
                            <td><strong>Subject(s) : </strong> <?= htmlspecialchars($subjects_display ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Education : </strong>
                            <?php 
                            $staffEducation = json_decode($data['education'] ?? '[]', true);
                            if (!empty($staffEducation) && is_array($staffEducation)) {
                                echo '<ul style="margin: 5px 0 0 0; padding-left: 15px;">';
                                foreach ($staffEducation as $edu) {
                                    echo '<li>' . htmlspecialchars($edu['education'] ?? '');
                                    if (!empty($edu['institute'])) {
                                        echo ' - ' . htmlspecialchars($edu['institute']);
                                    }
                                    if (!empty($edu['result'])) {
                                        echo ' (Result: ' . htmlspecialchars($edu['result']) . ')';
                                    }
                                    echo '</li>';
                                }
                                echo '</ul>';
                            } else {
                                echo nl2br(htmlspecialchars($data['education'] ?? 'N/A'));
                            }
                            ?>
                            </td>
                            <td><strong>Experience : </strong> <?= nl2br(htmlspecialchars($data['experience'])) ?></td>
                        </tr>
                    </table>

                    <div class="section-title">Address</div>
                    <table class="info-table">
                        <tr>
                            <td><strong>Present Address : </strong> <?= nl2br(htmlspecialchars($data['present_address'])) ?></td>
                            <td><strong>Permanent Address : </strong> <?= nl2br(htmlspecialchars($data['permanent_address'])) ?></td>
                        </tr>
                    </table>

                    <div class="section-title">Contact Information</div>
                    <table class="info-table">
                        <tr>
                            <td><strong>Phone : </strong> <?= htmlspecialchars($data['phone']) ?></td>
                            <td></td>
                        </tr>
                    </table>

                    <div class="signature">
                        <p style="font-size: 12px; text-align: left; font-weight: bold; text-decoration: underline; width: 50%;">Staff Signature</p>
                        <p style="width: 50%; font-size: 12px; text-align: right; margin-right: 50px; font-weight: bold; text-decoration: underline;">Authority Signature</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align: center;">
        <button class="download-btn" onclick="downloadProfile()">Download Profile</button>
        <a href="staff_profile_edit.php?id=<?= $id ?>" class="download-btn" style="background: #28a745; margin-left: 10px;">Edit Profile</a>
    </div>

</body>

</html>

<?php
require_once __DIR__ . '/../../env/session.php';
require_once __DIR__ . '/../../env/csrf.php';
include '../../env/config.php';

// Auth check - allow both admin and logged-in student
if (!isset($_SESSION['admin']) && !isset($_SESSION['student_email'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

$is_admin = isset($_SESSION['admin']);
$is_student = isset($_SESSION['student_email']) && !$is_admin;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student']) && $is_admin) {
    ams_csrf_verify_post();
    $del_id = intval($_POST['delete_id'] ?? 0);
    $table = $_POST['table'] ?? '';

    if ($del_id > 0 && ams_validate_student_table($conn, $table)) {
        $del_stmt = $conn->prepare("SELECT photo FROM `$table` WHERE id = ?");
        $del_stmt->bind_param("i", $del_id);
        $del_stmt->execute();
        $del_res = $del_stmt->get_result();
        if ($del_row = $del_res->fetch_assoc()) {
            $photoPath = $del_row['photo'] ?? '';
            if (!empty($photoPath) && strpos($photoPath, 'default-photo.jpg') === false) {
                $cleanPath = str_replace('../', '', $photoPath);
                $fsPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);
                if (file_exists($fsPath)) {
                    unlink($fsPath);
                }
            }
        }
        $del_stmt->close();

        $conn->query("DELETE FROM students_documents WHERE student_id = $del_id");
        $conn->query("DELETE FROM documents WHERE reference_type = 'student' AND reference_id = $del_id");
        $conn->query("DELETE FROM student_users WHERE student_id = $del_id");

        $del_stmt = $conn->prepare("DELETE FROM `$table` WHERE id = ?");
        $del_stmt->bind_param("i", $del_id);
        $del_stmt->execute();
        $del_stmt->close();
    }

    ams_redirect(ams_admin_url('view_students'));
    exit;
}

$table = $_GET['table'] ?? '';
$id = intval($_GET['id'] ?? 0);
$student = null;

if ($is_admin) {
    // Admin can view any student by table and id
    if (!$table || !$id || !ams_validate_student_table($conn, $table)) {
        die("Invalid request.");
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
} elseif ($is_student) {
    // Student can view their own profile
    $dynamicId = isset($_SESSION['student_dynamic_id']) ? (int)$_SESSION['student_dynamic_id'] : 0;
    $studentTable = $_SESSION['student_table'] ?? '';
    
    if (!$studentTable || !$dynamicId) {
        die("Student profile not found.");
    }

    $stmt = $conn->prepare("SELECT * FROM `$studentTable` WHERE id = ?");
    $stmt->bind_param("i", $dynamicId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        die("Student not found.");
    }

    $student = $result->fetch_assoc();
    $stmt->close();
}

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
    $cleanPath = str_replace('../', '', $photoPath);
    $fsPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);
    if (file_exists($fsPath)) {
        $photo = BASE_URL . '/' . $cleanPath;
    }
}
if (empty($photo)) {
    $photo = BASE_URL . '/uploads/students/default-photo.jpg';
}

$documents = [];
// First try new students_documents table
$doc_stmt = $conn->prepare("SELECT file_name, file_path FROM students_documents WHERE student_id = ? ORDER BY uploaded_at DESC");
$doc_stmt->bind_param("i", $id);
$doc_stmt->execute();
$doc_result = $doc_stmt->get_result();
while ($doc = $doc_result->fetch_assoc()) {
    $documents[] = $doc;
}
$doc_stmt->close();

// Fallback: check old documents table for backward compatibility
if (empty($documents)) {
    $old_doc_stmt = $conn->prepare("SELECT file_name, file_path FROM documents WHERE reference_type = 'student' AND reference_id = ? ORDER BY uploaded_at DESC");
    $old_doc_stmt->bind_param("i", $id);
    $old_doc_stmt->execute();
    $old_doc_result = $old_doc_stmt->get_result();
    while ($doc = $old_doc_result->fetch_assoc()) {
        $documents[] = $doc;
    }
    $old_doc_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - <?= htmlspecialchars($student['name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/library/css/student_profile_card.css">
</head>
<body class="sp-card-body">
    <div class="sp-card-wrapper">
        <div class="profile-card">
            <div class="profile-cover">
            </div>
            <div class="profile-avatar">
                <img src="<?= htmlspecialchars($photo) ?>" alt="Student Photo" class="profile-avatar-img" onerror="this.onerror=null; this.src='<?= htmlspecialchars(BASE_URL) ?>/uploads/students/default-photo.jpg';">
            </div>
            <div class="profile-id">
                <h2 class="profile-name"><?= htmlspecialchars($student['name']) ?></h2>
                <p class="profile-roll"><i class="fas fa-id-card"></i> Roll: <?= htmlspecialchars($student['roll']) ?></p>
                <div class="profile-badges">
                    <span class="pbadge pbadge-primary"><i class="fas fa-users"></i> <?= htmlspecialchars($batch_name) ?></span>
                    <span class="pbadge pbadge-secondary"><i class="fas fa-school"></i> <?= htmlspecialchars($class_name) ?></span>
                </div>
            </div>
            <div class="profile-actions">
                <?php if ($is_admin): ?>
                <a href="<?= ams_admin_url('student_profile_edit') ?>?table=<?= urlencode($table) ?>&id=<?= urlencode($id) ?>" class="btn-profile-edit">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </a>
                <form method="post" onsubmit="return confirm('Sure to delete this Student profile');" style="margin-top:12px;">
                    <?= ams_csrf_field() ?>
                    <input type="hidden" name="delete_id" value="<?= $id ?>">
                    <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                    <button type="submit" name="delete_student" class="btn-profile-edit btn-profile-delete">
                        <i class="fas fa-trash-alt"></i> Delete Profile
                    </button>
                </form>
                <?php else: ?>
                <a href="<?= ams_student_url('student_profile_edit') ?>" class="btn-profile-edit">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="profile-section">
            <h4><i class="fas fa-user"></i> Personal Information</h4>
            <div class="info-grid">
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-user"></i></span>
                    <span class="ii-text"><span class="ii-label">Name</span><span class="ii-value"><?= htmlspecialchars($student['name']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-venus-mars"></i></span>
                    <span class="ii-text"><span class="ii-label">Gender</span><span class="ii-value"><?= htmlspecialchars($student['gender']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-female"></i></span>
                    <span class="ii-text"><span class="ii-label">Mother's Name</span><span class="ii-value"><?= htmlspecialchars($student['mother_name']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-male"></i></span>
                    <span class="ii-text"><span class="ii-label">Father's Name</span><span class="ii-value"><?= htmlspecialchars($student['father_name']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-calendar-alt"></i></span>
                    <span class="ii-text"><span class="ii-label">Date of Birth</span><span class="ii-value"><?= htmlspecialchars($student['dob']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-tint"></i></span>
                    <span class="ii-text"><span class="ii-label">Blood Group</span><span class="ii-value"><?= htmlspecialchars($student['blood_group']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-pray"></i></span>
                    <span class="ii-text"><span class="ii-label">Religion</span><span class="ii-value"><?= htmlspecialchars($student['religion']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-flag"></i></span>
                    <span class="ii-text"><span class="ii-label">Nationality</span><span class="ii-value"><?= htmlspecialchars($student['nationality']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-id-card"></i></span>
                    <span class="ii-text"><span class="ii-label">Birth Certificate No.</span><span class="ii-value"><?= htmlspecialchars($student['birth_cert_no']) ?></span></span>
                </div>
            </div>
        </div>

        <div class="profile-section">
            <h4><i class="fas fa-map-marker-alt"></i> Address</h4>
            <div class="info-grid">
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-map-pin"></i></span>
                    <span class="ii-text"><span class="ii-label">Present Address</span><span class="ii-value"><?= nl2br(htmlspecialchars($student['present_address'])) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-home"></i></span>
                    <span class="ii-text"><span class="ii-label">Permanent Address</span><span class="ii-value"><?= nl2br(htmlspecialchars($student['permanent_address'])) ?></span></span>
                </div>
            </div>
        </div>

        <div class="profile-section">
            <h4><i class="fas fa-graduation-cap"></i> Academic Information</h4>
            <div class="info-grid">
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-users"></i></span>
                    <span class="ii-text"><span class="ii-label">Batch</span><span class="ii-value"><?= htmlspecialchars($batch_name) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-school"></i></span>
                    <span class="ii-text"><span class="ii-label">Class</span><span class="ii-value"><?= htmlspecialchars($class_name) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-id-card"></i></span>
                    <span class="ii-text"><span class="ii-label">Roll</span><span class="ii-value"><?= htmlspecialchars($student['roll']) ?></span></span>
                </div>
            </div>
        </div>

        <div class="profile-section">
            <h4><i class="fas fa-phone"></i> Contact Information</h4>
            <div class="info-grid">
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-phone"></i></span>
                    <span class="ii-text"><span class="ii-label">Father Mobile</span><span class="ii-value"><?= htmlspecialchars($student['father_mobile']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-phone"></i></span>
                    <span class="ii-text"><span class="ii-label">Mother Mobile</span><span class="ii-value"><?= htmlspecialchars($student['mother_mobile']) ?></span></span>
                </div>
            </div>
        </div>

        <div class="profile-section">
            <h4><i class="fas fa-user-friends"></i> Local Guardian</h4>
            <div class="info-grid">
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-user"></i></span>
                    <span class="ii-text"><span class="ii-label">Guardian Name</span><span class="ii-value"><?= htmlspecialchars($student['guardian_name']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-briefcase"></i></span>
                    <span class="ii-text"><span class="ii-label">Guardian Profession</span><span class="ii-value"><?= htmlspecialchars($student['guardian_profession']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-phone"></i></span>
                    <span class="ii-text"><span class="ii-label">Guardian Mobile</span><span class="ii-value"><?= htmlspecialchars($student['guardian_mobile']) ?></span></span>
                </div>
            </div>
        </div>

        <?php if (!empty($documents)): ?>
        <div class="profile-section">
            <h4><i class="fas fa-file-alt"></i> Documents</h4>
            <ul class="doc-list">
                <?php foreach ($documents as $doc): ?>
                    <li>
                        <a href="<?= $is_admin ? ams_admin_url('document_download') : ams_student_url('document_download') ?>?path=<?= urlencode($doc['file_path']) ?>" target="_blank">
                            <i class="fas fa-file-pdf"></i>
                            <?= htmlspecialchars($doc['file_name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
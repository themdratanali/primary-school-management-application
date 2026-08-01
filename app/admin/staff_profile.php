<?php
require_once __DIR__ . '/../../env/session.php';
require_once __DIR__ . '/../../env/csrf.php';
include '../../env/config.php';

// Auth check
if (!isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_staff'])) {
    ams_csrf_verify_post();
    $del_id = intval($_POST['delete_id'] ?? 0);

    if ($del_id > 0) {
        $del_stmt = $conn->prepare("SELECT photo FROM staff WHERE id = ?");
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

        $conn->query("DELETE FROM staff_documents WHERE staff_id = $del_id");
        $conn->query("DELETE FROM documents WHERE reference_type = 'staff' AND reference_id = $del_id");

        $del_stmt = $conn->prepare("DELETE FROM staff WHERE id = ?");
        $del_stmt->bind_param("i", $del_id);
        $del_stmt->execute();
        $del_stmt->close();
    }

    ams_redirect(ams_admin_url('staff_list'));
    exit;
}

// Validate staff ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Staff ID missing or invalid");
}

// Fetch staff profile data
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

$photo = '';
if (!empty($data['photo'])) {
    $photoPath = $data['photo'];
    $cleanPath = str_replace('../', '', $photoPath);
    $fsPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);
    if (file_exists($fsPath)) {
        $photo = BASE_URL . '/' . $cleanPath;
    }
}
if (empty($photo)) {
    $photo = BASE_URL . '/uploads/staff/default-photo.jpg';
}

$documents = [];
// First try new staff_documents table
$doc_stmt = $conn->prepare("SELECT file_name, file_path FROM staff_documents WHERE staff_id = ? ORDER BY uploaded_at DESC");
$doc_stmt->bind_param("i", $id);
$doc_stmt->execute();
$doc_result = $doc_stmt->get_result();
while ($doc = $doc_result->fetch_assoc()) {
    $documents[] = $doc;
}
$doc_stmt->close();

// Fallback: check old documents table for backward compatibility
if (empty($documents)) {
    $old_doc_stmt = $conn->prepare("SELECT file_name, file_path FROM documents WHERE reference_type = 'staff' AND reference_id = ? ORDER BY uploaded_at DESC");
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
    <title>Staff Profile - <?= htmlspecialchars($data['name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/library/css/profile_card.css">
</head>
<body class="ams-card-body">
    <div class="ams-card-wrapper">
        <div class="profile-card">
            <div class="profile-cover">
            </div>
            <div class="profile-avatar">
                <img src="<?= htmlspecialchars($photo) ?>" alt="Staff Photo" class="profile-avatar-img" onerror="this.onerror=null; this.src='<?= htmlspecialchars(BASE_URL) ?>/uploads/staff/default-photo.jpg';">
            </div>
            <div class="profile-id">
                <h2 class="profile-name"><?= htmlspecialchars($data['name']) ?></h2>
                <p class="profile-roll"><i class="fas fa-id-badge"></i> <?= htmlspecialchars($data['designation'] ?? 'Staff') ?></p>
                <div class="profile-badges">
                    <span class="pbadge pbadge-primary"><i class="fas fa-briefcase"></i> <?= htmlspecialchars($data['designation'] ?? 'Staff') ?></span>
                </div>
            </div>
            <div class="profile-actions">
                <a href="<?= ams_admin_url('staff_profile_edit') ?>?id=<?= $id ?>" class="btn-profile-edit">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </a>
                <form method="post" onsubmit="return confirm('Sure to delete this Staff profile');" style="margin-top:12px;">
                    <?= ams_csrf_field() ?>
                    <input type="hidden" name="delete_id" value="<?= $id ?>">
                    <button type="submit" name="delete_staff" class="btn-profile-edit btn-profile-delete">
                        <i class="fas fa-trash-alt"></i> Delete Profile
                    </button>
                </form>
            </div>
        </div>

        <div class="profile-section">
            <h4><i class="fas fa-user"></i> Personal Information</h4>
            <div class="info-grid">
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-user"></i></span>
                    <span class="ii-text"><span class="ii-label">Name</span><span class="ii-value"><?= htmlspecialchars($data['name']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-venus-mars"></i></span>
                    <span class="ii-text"><span class="ii-label">Gender</span><span class="ii-value"><?= htmlspecialchars($data['gender']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-female"></i></span>
                    <span class="ii-text"><span class="ii-label">Mother's Name</span><span class="ii-value"><?= htmlspecialchars($data['mother_name']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-male"></i></span>
                    <span class="ii-text"><span class="ii-label">Father's Name</span><span class="ii-value"><?= htmlspecialchars($data['father_name']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-calendar-alt"></i></span>
                    <span class="ii-text"><span class="ii-label">Date of Birth</span><span class="ii-value"><?= htmlspecialchars($data['dob']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-tint"></i></span>
                    <span class="ii-text"><span class="ii-label">Blood Group</span><span class="ii-value"><?= htmlspecialchars($data['blood_group']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-pray"></i></span>
                    <span class="ii-text"><span class="ii-label">Religion</span><span class="ii-value"><?= htmlspecialchars($data['religion']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-flag"></i></span>
                    <span class="ii-text"><span class="ii-label">Nationality</span><span class="ii-value"><?= htmlspecialchars($data['nationality']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-id-card"></i></span>
                    <span class="ii-text"><span class="ii-label">NID</span><span class="ii-value"><?= htmlspecialchars($data['nid']) ?></span></span>
                </div>
            </div>
        </div>

        <div class="profile-section">
            <h4><i class="fas fa-map-marker-alt"></i> Address</h4>
            <div class="info-grid">
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-map-pin"></i></span>
                    <span class="ii-text"><span class="ii-label">Present Address</span><span class="ii-value"><?= nl2br(htmlspecialchars($data['present_address'])) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-home"></i></span>
                    <span class="ii-text"><span class="ii-label">Permanent Address</span><span class="ii-value"><?= nl2br(htmlspecialchars($data['permanent_address'])) ?></span></span>
                </div>
            </div>
        </div>

        <div class="profile-section">
            <h4><i class="fas fa-briefcase"></i> Professional Information</h4>
            <div class="info-grid">
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-id-badge"></i></span>
                    <span class="ii-text"><span class="ii-label">Designation</span><span class="ii-value"><?= htmlspecialchars($data['designation'] ?? 'N/A') ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-graduation-cap"></i></span>
                    <span class="ii-text">
                        <span class="ii-label">Education</span>
                        <span class="ii-value">
                            <?php
                            $staffEducation = json_decode($data['education'] ?? '[]', true);
                            if (!empty($staffEducation) && is_array($staffEducation)) {
                                echo '<ul style="margin:0; padding-left:18px;">';
                                foreach ($staffEducation as $edu) {
                                    echo '<li>';
                                    echo htmlspecialchars($edu['education'] ?? '');
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
                                echo htmlspecialchars($data['education'] ?? 'N/A');
                            }
                            ?>
                        </span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-briefcase"></i></span>
                    <span class="ii-text"><span class="ii-label">Experience</span><span class="ii-value"><?= nl2br(htmlspecialchars($data['experience'])) ?></span></span>
                </div>
            </div>
        </div>

        <div class="profile-section">
            <h4><i class="fas fa-phone"></i> Contact Information</h4>
            <div class="info-grid">
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-phone"></i></span>
                    <span class="ii-text"><span class="ii-label">Phone</span><span class="ii-value"><?= htmlspecialchars($data['phone']) ?></span></span>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><i class="fas fa-envelope"></i></span>
                    <span class="ii-text"><span class="ii-label">Email</span><span class="ii-value"><?= htmlspecialchars($data['email'] ?? 'N/A') ?></span></span>
                </div>
            </div>
        </div>

        <?php if (!empty($documents)): ?>
        <div class="profile-section">
            <h4><i class="fas fa-file-alt"></i> Documents</h4>
            <ul class="doc-list">
                <?php foreach ($documents as $doc): ?>
                    <li>
                        <a href="<?= ams_admin_url('document_download') ?>?path=<?= urlencode($doc['file_path']) ?>" target="_blank">
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
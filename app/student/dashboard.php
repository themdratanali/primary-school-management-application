<?php
require_once __DIR__ . '/../../env/session.php';
include '../../env/config.php';

// Auth check
if (!isset($_SESSION['student_email'])) {
    ams_redirect(ams_student_url('login'));
    exit;
}

// Session variables
$email = $_SESSION['student_email'];
$student = null;
$studentTable = $_SESSION['student_table'] ?? '';
$dynamicId = isset($_SESSION['student_dynamic_id']) ? (int)$_SESSION['student_dynamic_id'] : 0;

// Helper to load student row from dynamic table
$loadStudentRow = function(string $table, int $id) use ($conn): ?array {
    if ($table === '' || $id <= 0) {
        return null;
    }
    $stmt = $conn->prepare("SELECT * FROM `$table` WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res && $res->num_rows === 1 ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
};

if ($studentTable && $dynamicId > 0) {
    $student = $loadStudentRow($studentTable, $dynamicId);
}

if (!$student) {
    $mapStmt = $conn->prepare("SELECT id, student_id FROM student_users WHERE email = ? LIMIT 1");
    if ($mapStmt) {
        $mapStmt->bind_param("s", $email);
        $mapStmt->execute();
        $mapRes = $mapStmt->get_result();
        $userMap = $mapRes && $mapRes->num_rows === 1 ? $mapRes->fetch_assoc() : null;
        $mapStmt->close();

        if ($userMap && (int)$userMap['student_id'] > 0) {
            $dynamicId = (int)$userMap['student_id'];
            $tablesRes = $conn->query("SHOW TABLES LIKE 'Student\_%'");
            if ($tablesRes) {
                while ($tblRow = $tablesRes->fetch_row()) {
                    $tableName = $tblRow[0] ?? '';
                    if ($tableName === '') {
                        continue;
                    }
                    $row = $loadStudentRow($tableName, $dynamicId);
                    if ($row) {
                        $student = $row;
                        $studentTable = $tableName;
                        $_SESSION['student_dynamic_id'] = $dynamicId;
                        $_SESSION['student_table'] = $tableName;
                        $_SESSION['student_name'] = $row['name'] ?? '';
                        $_SESSION['student_roll'] = $row['roll'] ?? '';
                        $_SESSION['student_batch_id'] = isset($row['batch_id']) ? (int)$row['batch_id'] : null;
                        $_SESSION['student_class_id'] = isset($row['class_id']) ? (int)$row['class_id'] : null;
                        break;
                    }
                }
            }
        }
    }
}

if (!$student || !$studentTable || $dynamicId <= 0) {
    http_response_code(404);
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Student Dashboard</title></head><body>";
    echo "<p style='font-family: Arial, sans-serif; color:#b91c1c; text-align:center; margin-top:40px;'>Student record not found for this account.</p>";
    echo "</body></html>";
    exit;
}

$student_name = $_SESSION['student_name'] ?? $student['name'];
$student_roll = $_SESSION['student_roll'] ?? $student['roll'];

$batch_name = 'N/A';
$student_batch_id = $_SESSION['student_batch_id'] ?? 0;
$student_class_id = $_SESSION['student_class_id'] ?? 0;
$batch_id = $student_batch_id;
$class_id = $student_class_id;
if ($batch_id > 0) {
    $batch_stmt = $conn->prepare("SELECT name FROM batches WHERE id = ? LIMIT 1");
    if ($batch_stmt) {
        $batch_stmt->bind_param("i", $batch_id);
        $batch_stmt->execute();
        $batch_res = $batch_stmt->get_result();
        if ($batch_res && $batch_res->num_rows > 0) {
            $batch_row = $batch_res->fetch_assoc();
            $batch_name = $batch_row['name'];
        }
        $batch_stmt->close();
    }
}

// Promotion history - search all batch tables for student records
$promotion_history = [];

$allBatchesRes = $conn->query("SELECT id, name FROM batches ORDER BY name DESC");
if ($allBatchesRes) {
    while ($batchRow = $allBatchesRes->fetch_assoc()) {
        $batchSlug = strtolower(str_replace(' ', '_', $batchRow['name']));
        $batchId = (int)$batchRow['id'];
        
        $tablesRes = $conn->query("SHOW TABLES LIKE 'Student_{$batchSlug}_%'");
        if ($tablesRes) {
            while ($tblRow = $tablesRes->fetch_row()) {
                $tableName = $tblRow[0];
                $checkStmt = $conn->prepare("SELECT class_id FROM `$tableName` WHERE id = ? LIMIT 1");
                if ($checkStmt) {
                    $checkStmt->bind_param("i", $dynamicId);
                    $checkStmt->execute();
                    $checkRes = $checkStmt->get_result();
                    if ($checkRes && $checkRes->num_rows > 0) {
                        $classRow = $checkRes->fetch_assoc();
                        $histClassId = (int)$classRow['class_id'];
                        
                        $classNameStmt = $conn->prepare("SELECT name FROM classes WHERE id = ? LIMIT 1");
                        if ($classNameStmt) {
                            $classNameStmt->bind_param("i", $histClassId);
                            $classNameStmt->execute();
                            $classNameRes = $classNameStmt->get_result();
                            if ($classNameRes && $classNameRes->num_rows > 0) {
                                $classNameRow = $classNameRes->fetch_assoc();
                                $promotion_history[] = [
                                    'table' => $tableName,
                                    'batch_id' => $batchId,
                                    'batch_name' => $batchRow['name'],
                                    'class_id' => $histClassId,
                                    'class_name' => $classNameRow['name'],
                                    'is_current' => ($batchId === $student_batch_id && $histClassId === $student_class_id)
                                ];
                            }
                            $classNameStmt->close();
                        }
                    }
                    $checkStmt->close();
                }
            }
        }
    }
}

usort($promotion_history, function($a, $b) {
    return strcmp($b['batch_name'], $a['batch_name']);
});

if (!empty($promotion_history)) {
    $current_promo = $promotion_history[0];
    $batch_name = $current_promo['batch_name'];
    $class_name = $current_promo['class_name'];
    $student_batch_id = $current_promo['batch_id'];
    $student_class_id = $current_promo['class_id'];
    $student_roll = $current_promo['roll'] ?? $student_roll;
    
    $_SESSION['student_batch_id'] = $student_batch_id;
    $_SESSION['student_class_id'] = $student_class_id;
    $_SESSION['student_roll'] = $student_roll;
}

$class_name = 'N/A';
if ($student_class_id > 0) {
    $class_stmt = $conn->prepare("SELECT name FROM classes WHERE id = ? LIMIT 1");
    if ($class_stmt) {
        $class_stmt->bind_param("i", $student_class_id);
        $class_stmt->execute();
        $class_res = $class_stmt->get_result();
        if ($class_res && $class_res->num_rows > 0) {
            $class_row = $class_res->fetch_assoc();
            $class_name = $class_row['name'];
        }
        $class_stmt->close();
    }
}

$student_user_id = 0;
$user_stmt = $conn->prepare("SELECT id, plain_password FROM student_users WHERE email = ? LIMIT 1");
if ($user_stmt) {
    $user_stmt->bind_param("s", $email);
    $user_stmt->execute();
    $user_res = $user_stmt->get_result();
    if ($user_res && $user_res->num_rows > 0) {
        $user_row = $user_res->fetch_assoc();
        $student_user_id = $user_row['id'];
        $student_plain_password = $user_row['plain_password'] ?? '';
    }
    $user_stmt->close();
}

$student_photo = $student['photo'] ?? '';
$photo_path = '';

if (!empty($student_photo)) {
    if (file_exists($student_photo)) {
        $photo_path = $student_photo;
    } elseif (file_exists('../../' . $student_photo)) {
        $photo_path = '../../' . $student_photo;
    } elseif (file_exists(ams_upload_dir('students') . basename($student_photo))) {
        $photo_path = ams_upload_dir('students') . basename($student_photo);
    }
}

if (empty($photo_path)) {
    $photo_path = 'https://ui-avatars.com/api/?name=' . urlencode($student_name) . '&background=177a03&color=fff&size=150';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - Student Dashboard</title>
    <link rel="shortcut icon" type="image/jpg" href="<?php echo BASE_URL; ?>/uploads/images/এ্যাপেক্স মডেল স্কুল.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/dashboard.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 500px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        /* Mobile modal centering */
        @media (max-width: 768px) {
            .modal-content {
                margin: 50% auto !important;
                width: 85% !important;
                max-width: 350px !important;
            }
        }
        .modal-content h3 {
            margin-top: 0;
            color: #333;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        /* Sidebar Styles */
        .sidebar {
            width: 230px;
            background: linear-gradient(180deg, #177a03 0%, #145a02 100%);
            color: white;
            flex-direction: column;
            padding: 20px;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 999;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        }
        .sidebar.active {
            transform: translateX(0);
            box-shadow: 6px 0 25px rgba(0,0,0,0.15);
        }
        .sidebar.active ~ .main-content {
            margin-left: 260px;
        }
        .sidebar h4 {
            text-align: center;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 17px;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.95);
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 8px;
            color: rgba(255,255,255,0.94);
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            margin-bottom: 6px;
            transition: all 0.2s ease;
        }
        .sidebar a:hover {
            background: rgba(255,255,255,0.15);
            transform: translateX(3px);
        }
        .sidebar a.active {
            background: rgba(255,255,255,0.25);
            font-weight: 600;
            border-left: 4px solid rgba(255,255,255,0.8);
        }
        .logout-link {
            margin-top: auto;
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        
        /* Mobile Bottom Navigation - hidden on desktop */
        .mobile-bottom-nav {
            display: none;
        }
        
        .sidebar-toggle {
            display: flex;
            position: fixed;
            top: 12px;
            left: 12px;
            z-index: 1001;
            background: linear-gradient(135deg, #177a03 0%, #145a02 100%);
            color: white;
            border: none;
            padding: 10px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            align-items: center;
            justify-content: center;
        }
        .sidebar-toggle:hover {
            background: linear-gradient(135deg, #145a02 0%, #0f3d01 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        .sidebar-toggle:active {
            transform: translateY(0);
        }
        .sidebar-toggle i {
            transition: transform 0.3s ease;
        }
        .sidebar-toggle.toggle-active i {
            transform: rotate(90deg);
        }
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 998;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .sidebar-overlay.active {
            display: block;
            opacity: 1;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed !important;
                left: 0;
                top: 0;
                width: 220px;
                height: 100vh;
                transform: translateX(-100%) !important;
                transition: transform 0.3s ease;
                z-index: 999;
                overflow-y: auto;
                padding-top: 60px;
            }
            .sidebar.active {
                transform: translateX(0) !important;
            }
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 15px !important;
                padding-bottom: 80px !important;
            }
            
            /* Mobile Bottom Navigation */
            .mobile-bottom-nav {
                display: flex !important;
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                height: 65px;
                background: linear-gradient(135deg, #177a03 0%, #145a02 100%);
                justify-content: space-around;
                align-items: center;
                z-index: 1000;
                box-shadow: 0 -4px 10px rgba(0,0,0,0.1);
            }
            .mobile-bottom-nav a {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                color: rgba(255,255,255,0.7);
                text-decoration: none;
                font-size: 10px;
                padding: 5px 8px;
                border-radius: 8px;
                transition: all 0.2s ease;
            }
            .mobile-bottom-nav a i {
                font-size: 18px;
                margin-bottom: 2px;
            }
            .mobile-bottom-nav a:hover,
            .mobile-bottom-nav a.active {
                color: white;
                background: rgba(255,255,255,0.15);
            }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav">
        <a href="<?php echo ams_student_url('dashboard'); ?>" class="active">
            <i class="fas fa-id-badge"></i>
            <span>Profile</span>
        </a>
        <a href="<?php echo ams_student_url('results'); ?>">
            <i class="fas fa-clipboard-list"></i>
            <span>Results</span>
        </a>
        <a href="<?php echo ams_student_url('fees'); ?>">
            <i class="fas fa-coins"></i>
            <span>Fee Summary</span>
        </a>
        <a href="javascript:void(0);" onclick="confirmLogout();">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>

    <div class="sidebar" id="sidebar">
        <h4>Apex Model School</h4>
        <a href="<?php echo ams_student_url('dashboard'); ?>" class="active">
            <i class="fas fa-id-badge"></i> <span>Profile</span>
        </a>
        <a href="<?php echo ams_student_url('results'); ?>">
            <i class="fas fa-clipboard-list"></i> <span>Results</span>
        </a>
        <a href="<?php echo ams_student_url('fees'); ?>">
            <i class="fas fa-coins"></i> <span>Fee Summary</span>
        </a>
        <div class="logout-link">
            <a href="javascript:void(0);" onclick="confirmLogout();">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="profile-card">
                        <div class="profile-cover">
                            <span class="profile-cover-school"><i class="fas fa-graduation-cap"></i> Apex Model School</span>
                        </div>
                        <div class="profile-avatar">
                            <img src="<?= htmlspecialchars($photo_path) ?>" alt="Student Photo" class="profile-avatar-img" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?= urlencode($student_name) ?>&background=177a03&color=fff&size=200';">
                        </div>
                        <div class="profile-id">
                            <h2 class="profile-name"><?= htmlspecialchars($student_name) ?></h2>
                            <p class="profile-roll"><i class="fas fa-id-card"></i> Roll: <?= htmlspecialchars($student_roll) ?></p>
                            <div class="profile-badges">
                                <span class="pbadge pbadge-primary"><i class="fas fa-users"></i> <?= htmlspecialchars($batch_name) ?></span>
                                <span class="pbadge pbadge-secondary"><i class="fas fa-school"></i> <?= htmlspecialchars($class_name) ?></span>
                            </div>
                        </div>
                        <div class="profile-actions">
                            <a href="<?php echo ams_student_url('student_profile_edit'); ?>" class="btn-profile-edit">
                                <i class="fas fa-user-edit"></i> Edit Profile
                            </a>
                        </div>
                    </div>
                    
                    <div class="profile-section" style="margin-top: 15px;">
                        <h4><i class="fas fa-user-graduate"></i> Current Info</h4>
                        <?php 
                        usort($promotion_history, function($a, $b) {
                            return strcmp($b['batch_name'], $a['batch_name']);
                        });
                        
                        $current_promotion = null;
                        if (!empty($promotion_history)) {
                            $current_promotion = $promotion_history[0];
                        }
                        ?>
                        <div class="info-grid">
                        <?php if ($current_promotion): ?>
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-calendar-alt"></i></span>
                                <span class="ii-text"><span class="ii-label">Batch</span><span class="ii-value"><?= htmlspecialchars($current_promotion['batch_name']) ?></span></span>
                            </div>
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-school"></i></span>
                                <span class="ii-text"><span class="ii-label">Class</span><span class="ii-value"><?= htmlspecialchars($current_promotion['class_name']) ?></span></span>
                            </div>
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-id-card"></i></span>
                                <span class="ii-text"><span class="ii-label">Roll</span><span class="ii-value"><?= htmlspecialchars($current_promotion['roll'] ?? $student_roll) ?></span></span>
                            </div>
                        <?php else: ?>
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-calendar-alt"></i></span>
                                <span class="ii-text"><span class="ii-label">Batch</span><span class="ii-value"><?= htmlspecialchars($batch_name) ?></span></span>
                            </div>
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-school"></i></span>
                                <span class="ii-text"><span class="ii-label">Class</span><span class="ii-value"><?= htmlspecialchars($class_name) ?></span></span>
                            </div>
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-id-card"></i></span>
                                <span class="ii-text"><span class="ii-label">Roll</span><span class="ii-value"><?= htmlspecialchars($student_roll) ?></span></span>
                            </div>
                        <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (count($promotion_history) > 1): ?>
                    <div class="profile-section" style="margin-top: 15px;">
                        <h4><i class="fas fa-history"></i> Promotion History</h4>
                        <div class="timeline">
                        <?php foreach ($promotion_history as $index => $hist): ?>
                            <div class="timeline-item<?= $index === 0 ? ' current' : '' ?>">
                                <span class="timeline-dot"><i class="fas <?= $index === 0 ? 'fa-user-graduate' : 'fa-graduation-cap' ?>"></i></span>
                                <div class="timeline-body">
                                    <span class="timeline-title"><?= htmlspecialchars($hist['batch_name']) ?> - <?= htmlspecialchars($hist['class_name']) ?></span>
                                    <span class="timeline-sub">Roll: <?= htmlspecialchars($hist['roll'] ?? 'N/A') ?><?php if ($index === 0): ?> <span class="pbadge pbadge-primary" style="font-size:11px;padding:2px 8px;">Current</span><?php endif; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-lg-8">
                    <div class="profile-section">
                        <h4><i class="fas fa-user"></i> Personal Information</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-user"></i></span>
                                <span class="ii-text"><span class="ii-label">Name</span><span class="ii-value"><?= htmlspecialchars($student['name']) ?></span></span>
                            </div>
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-female"></i></span>
                                <span class="ii-text"><span class="ii-label">Mother's Name</span><span class="ii-value"><?= htmlspecialchars($student['mother_name'] ?? '-') ?></span></span>
                            </div>
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-male"></i></span>
                                <span class="ii-text"><span class="ii-label">Father's Name</span><span class="ii-value"><?= htmlspecialchars($student['father_name'] ?? '-') ?></span></span>
                            </div>
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-venus-mars"></i></span>
                                <span class="ii-text"><span class="ii-label">Gender</span><span class="ii-value"><?= htmlspecialchars($student['gender'] ?? '-') ?></span></span>
                            </div>
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-calendar-alt"></i></span>
                                <span class="ii-text"><span class="ii-label">Date of Birth</span><span class="ii-value"><?= htmlspecialchars($student['dob'] ?? '-') ?></span></span>
                            </div>
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-id-card"></i></span>
                                <span class="ii-text"><span class="ii-label">Birth Certificate No</span><span class="ii-value"><?= htmlspecialchars($student['birth_cert_no'] ?? '-') ?></span></span>
                            </div>
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-tint"></i></span>
                                <span class="ii-text"><span class="ii-label">Blood Group</span><span class="ii-value"><?= htmlspecialchars($student['blood_group'] ?? '-') ?></span></span>
                            </div>
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-pray"></i></span>
                                <span class="ii-text"><span class="ii-label">Religion</span><span class="ii-value"><?= htmlspecialchars($student['religion'] ?? '-') ?></span></span>
                            </div>
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-flag"></i></span>
                                <span class="ii-text"><span class="ii-label">Nationality</span><span class="ii-value"><?= htmlspecialchars($student['nationality'] ?? '-') ?></span></span>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section">
                        <h4><i class="fas fa-map-marker-alt"></i> Address Information</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-map-pin"></i></span>
                                <span class="ii-text"><span class="ii-label">Present Address</span><span class="ii-value"><?= nl2br(htmlspecialchars($student['present_address'] ?? '-')) ?></span></span>
                            </div>
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-home"></i></span>
                                <span class="ii-text"><span class="ii-label">Permanent Address</span><span class="ii-value"><?= nl2br(htmlspecialchars($student['permanent_address'] ?? '-')) ?></span></span>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section">
                        <h4><i class="fas fa-phone"></i> Contact Information</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-phone"></i></span>
                                <span class="ii-text"><span class="ii-label">Father Mobile</span><span class="ii-value"><?= htmlspecialchars($student['father_mobile'] ?? '-') ?></span></span>
                            </div>
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-phone"></i></span>
                                <span class="ii-text"><span class="ii-label">Mother Mobile</span><span class="ii-value"><?= htmlspecialchars($student['mother_mobile'] ?? '-') ?></span></span>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section">
                        <h4><i class="fas fa-user-shield"></i> Local Guardian</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-user"></i></span>
                                <span class="ii-text"><span class="ii-label">Guardian Name</span><span class="ii-value"><?= htmlspecialchars($student['guardian_name'] ?? '-') ?></span></span>
                            </div>
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-briefcase"></i></span>
                                <span class="ii-text"><span class="ii-label">Guardian Profession</span><span class="ii-value"><?= htmlspecialchars($student['guardian_profession'] ?? '-') ?></span></span>
                            </div>
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-phone"></i></span>
                                <span class="ii-text"><span class="ii-label">Guardian Mobile</span><span class="ii-value"><?= htmlspecialchars($student['guardian_mobile'] ?? '-') ?></span></span>
                            </div>
                            <div class="info-item">
                                <span class="ii-icon"><i class="fas fa-map-marker-alt"></i></span>
                                <span class="ii-text"><span class="ii-label">Guardian Address</span><span class="ii-value"><?= nl2br(htmlspecialchars($student['guardian_address'] ?? '-')) ?></span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/library/js/myscript.js"></script>
    <script>
    // Define toggleSidebar globally - elements accessed inside function
    window.toggleSidebar = function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainContent = document.querySelector('.main-content');
        
        if (!sidebar || !sidebarToggle) return;
        
        // Toggle active class for sidebar
        sidebar.classList.toggle('active');
        sidebarToggle.classList.toggle('toggle-active');
        
        if (sidebarOverlay) {
            sidebarOverlay.classList.toggle('active');
        }
        
        // Toggle main-content margin
        if (mainContent) {
            if (sidebar.classList.contains('active')) {
                mainContent.style.marginLeft = '260px';
            } else {
                mainContent.style.marginLeft = '0';
            }
        }
        
        // Save state
        const isActive = sidebar.classList.contains('active');
        localStorage.setItem('studentSidebarState', isActive ? 'open' : 'closed');
    };
    
    document.addEventListener('DOMContentLoaded', function() {
        // Add click handler for toggle button
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleSidebar);
        }
        
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');
        
        // Close on overlay click
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', toggleSidebar);
        }
        
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar && sidebar.classList.contains('active')) {
                toggleSidebar();
            }
        });
        
        // Restore saved state
        const savedState = localStorage.getItem('studentSidebarState');
        if (savedState === 'open') {
            sidebar.classList.add('active');
            sidebarToggle.classList.add('toggle-active');
            sidebarOverlay.classList.add('active');
            if (mainContent) {
                mainContent.style.marginLeft = '260px';
            }
        }
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 768) {
                if (sidebarOverlay) {
                    sidebarOverlay.classList.remove('active');
                }
                document.body.classList.remove('sidebar-open');
            }
        });
    });

    function confirmLogout() {
        var result = confirm('Are you sure you want to logout?');
        if (result) {
            window.location.href = 'student_logout';
        }
    }
    </script>
</body>
</html>












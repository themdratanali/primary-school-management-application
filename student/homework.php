<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['student_email'])) {
    header('Location: login.php');
    exit;
}

$email = $_SESSION['student_email'];

// Get student info
$student = null;
$studentTable = $_SESSION['student_table'] ?? '';
$dynamicId = isset($_SESSION['student_dynamic_id']) ? (int)$_SESSION['student_dynamic_id'] : 0;

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
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Homework</title></head><body>";
    echo "<p style='font-family: Arial, sans-serif; color:#b91c1c; text-align:center; margin-top:40px;'>Student record not found for this account.</p>";
    echo "</body></html>";
    exit;
}

$student_batch_id = $_SESSION['student_batch_id'] ?? 0;
$student_class_id = $_SESSION['student_class_id'] ?? 0;
$student_name = $_SESSION['student_name'] ?? $student['name'];

// Get student photo
$student_photo = $student['photo'] ?? '';
$photo_path = '';
if ($student_photo) {
    if (file_exists($student_photo)) {
        $photo_path = $student_photo;
    } elseif (file_exists('../' . $student_photo)) {
        $photo_path = '../' . $student_photo;
    } elseif (file_exists('../uploads/students/' . basename($student_photo))) {
        $photo_path = '../uploads/students/' . basename($student_photo);
    }
}
if (empty($photo_path)) {
    $photo_path = 'https://ui-avatars.com/api/?name=' . urlencode($student_name) . '&background=177a03&color=fff&size=150';
}

// Get student's homework
$homeworks = $conn->query("SELECT h.*, b.name as batch_name, c.name as class_name, t.name as teacher_name 
    FROM homeworks h 
    LEFT JOIN batches b ON h.batch_id = b.id 
    LEFT JOIN classes c ON h.class_id = c.id 
    LEFT JOIN teachers t ON h.teacher_id = t.id 
    WHERE h.batch_id = $student_batch_id AND h.class_id = $student_class_id AND h.status = 'active'
    ORDER BY h.created_at DESC");

$completed_homeworks = $conn->query("SELECT h.*, b.name as batch_name, c.name as class_name, t.name as teacher_name 
    FROM homeworks h 
    LEFT JOIN batches b ON h.batch_id = b.id 
    LEFT JOIN classes c ON h.class_id = c.id 
    LEFT JOIN teachers t ON h.teacher_id = t.id 
    WHERE h.batch_id = $student_batch_id AND h.class_id = $student_class_id AND h.status = 'done'
    ORDER BY h.updated_at DESC");

// Get single homework details
$homework_details = null;
if (isset($_GET['view'])) {
    $view_id = intval($_GET['view']);
    $stmt = $conn->prepare("SELECT h.*, b.name as batch_name, c.name as class_name, t.name as teacher_name 
        FROM homeworks h 
        LEFT JOIN batches b ON h.batch_id = b.id 
        LEFT JOIN classes c ON h.class_id = c.id 
        LEFT JOIN teachers t ON h.teacher_id = t.id 
        WHERE h.id = ? AND h.batch_id = ? AND h.class_id = ?");
    $stmt->bind_param("iii", $view_id, $student_batch_id, $student_class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $homework_details = $result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home Work - Student Dashboard</title>
    <link rel="shortcut icon" type="image/jpg" href="../assets/img/এ্যাপেক্স মডেল স্কুল.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/apex/assets/fontawesome/fontawesome-free-6.4.0-web/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .main-content {
            padding: 20px;
        }
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
                padding-bottom: 80px;
            }
        }
        
        /* Sidebar Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 998;
        }
        .sidebar-overlay.active {
            display: block;
        }
        
        /* Sidebar */
        .sidebar {
            width: 230px;
            background: linear-gradient(180deg, #177a03 0%, #145a02 100%);
            color: white;
            padding: 20px;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 999;
        }
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
        }
        .sidebar h4 {
            text-align: center;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 17px;
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
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
                padding-bottom: 80px;
            }
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar.active {
                transform: translateX(0);
            }
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
                padding-bottom: 80px;
            }
        }
        .page-header {
            background: linear-gradient(135deg, #177a03 0%, #145c02 100%);
            color: white;
            padding: 20px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(23, 122, 3, 0.3);
        }
        .page-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }
        .page-title i {
            margin-right: 10px;
        }
        .homework-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-left: 4px solid #177a03;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .homework-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        .homework-card.completed {
            border-left-color: #6c757d;
            opacity: 0.85;
        }
        .homework-card.completed:hover {
            opacity: 1;
        }
        .homework-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .homework-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin: 0;
        }
        .homework-date {
            font-size: 12px;
            color: #666;
            background: #f8f9fa;
            padding: 4px 10px;
            border-radius: 20px;
        }
        .homework-meta {
            font-size: 13px;
            color: #666;
            margin-bottom: 12px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .homework-meta i {
            color: #177a03;
            margin-right: 5px;
        }
        .homework-details {
            font-size: 14px;
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .homework-file {
            display: inline-flex;
            align-items: center;
            padding: 10px 15px;
            background: #e8f5e9;
            border-radius: 8px;
            text-decoration: none;
            color: #177a03;
            font-weight: 500;
            transition: background 0.2s;
        }
        .homework-file:hover {
            background: #c8e6c9;
            text-decoration: none;
        }
        .homework-file i {
            margin-right: 8px;
        }
        .btn-view {
            background: #177a03;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            transition: background 0.2s;
        }
        .btn-view:hover {
            background: #145c02;
            color: white;
        }
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #177a03;
            display: flex;
            align-items: center;
        }
        .section-title i {
            color: #177a03;
            margin-right: 10px;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }
        .homework-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
        }
        @media (max-width: 768px) {
            .homework-grid {
                grid-template-columns: 1fr;
            }
            .homework-header {
                flex-direction: column;
                gap: 10px;
            }
        }
        .modal-header {
            background: #177a03;
            color: white;
        }
        .modal-header .btn-close {
            filter: invert(1);
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active {
            background: #d1e7dd;
            color: #0f5132;
        }
        .status-done {
            background: #6c757d;
            color: white;
        }
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
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 600px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        @media (max-width: 768px) {
            .modal-content {
                margin: 20% auto !important;
                width: 85% !important;
            }
        }
        .btn-close-modal {
            background: #6c757d;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .btn-close-modal:hover {
            background: #5a6268;
        }
        
        /* Sidebar Toggle Button */
        .sidebar-toggle {
            display: none;
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
        }
        .sidebar-toggle:hover {
            background: #145c02;
        }
        
        @media (max-width: 768px) {
            .sidebar-toggle {
                display: flex !important;
            }
        }
        
        /* Mobile Bottom Navigation */
        .mobile-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 65px;
            background: linear-gradient(135deg, #177a03 0%, #145a02 100%);
            justify-content: space-around;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.2);
            padding: 0 3px;
        }
        
        .mobile-bottom-nav a {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-size: 9px;
            padding: 4px 5px;
            border-radius: 6px;
            transition: all 0.2s ease;
            flex: 1;
            height: 100%;
            max-width: 65px;
        }
        
        .mobile-bottom-nav a i {
            font-size: 16px;
            margin-bottom: 2px;
        }
        
        .mobile-bottom-nav a:hover,
        .mobile-bottom-nav a.active {
            color: white;
            background: rgba(255,255,255,0.2);
        }
        
        @media (max-width: 768px) {
            .mobile-bottom-nav {
                display: flex !important;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-bottom-nav {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav">
        <a href="dashboard.php">
            <i class="fas fa-id-badge"></i>
            <span>Profile</span>
        </a>
        <a href="student_results.php">
            <i class="fas fa-clipboard-list"></i>
            <span>Results</span>
        </a>
        <a href="homework.php" class="active">
            <i class="fas fa-book"></i>
            <span>Home Work</span>
        </a>
        <a href="student_fees.php">
            <i class="fas fa-coins"></i>
            <span>Fee Summary</span>
        </a>
        <a href="javascript:void(0);" onclick="openPasswordModal();">
            <i class="fas fa-key"></i>
            <span>Password</span>
        </a>
        <a href="javascript:void(0);" onclick="confirmLogout();">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="sidebar">
        <h4>Apex Model School</h4>
        <a href="dashboard.php">
            <i class="fas fa-id-badge"></i> <span>Profile</span>
        </a>
        <a href="student_results.php">
            <i class="fas fa-clipboard-list"></i> <span>Results</span>
        </a>
        <a href="homework.php" class="active">
            <i class="fas fa-book"></i> <span>Home Work</span>
        </a>
        <a href="student_fees.php">
            <i class="fas fa-coins"></i> <span>Fee Summary</span>
        </a>
        <a href="javascript:void(0);" onclick="openPasswordModal()">
            <i class="fas fa-key"></i> <span>Change Password</span>
        </a>
        <div class="logout-link">
            <a href="javascript:void(0);" onclick="confirmLogout();">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
            <h2 class="page-title"><i class="fas fa-book-open"></i> My Home Work</h2>
        </div>
        
        <?php if ($homework_details): ?>
            <!-- Homework Details Modal -->
            <div class="modal d-block" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?php echo htmlspecialchars($homework_details['title']); ?></h5>
                            <a href="homework.php" class="btn-close btn-close-white"></a>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p><strong><i class="fas fa-user"></i> Teacher:</strong> <?php echo htmlspecialchars($homework_details['teacher_name'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong><i class="fas fa-calendar"></i> Date:</strong> <?php echo date('d M Y, h:i A', strtotime($homework_details['created_at'])); ?></p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <p><strong><i class="fas fa-info-circle"></i> Details:</strong></p>
                                <div class="homework-details">
                                    <?php echo nl2br(htmlspecialchars($homework_details['details'] ?: 'No details provided.')); ?>
                                </div>
                            </div>
                            <?php if (!empty($homework_details['file_path'])): ?>
                                <div class="mb-3">
                                    <p><strong><i class="fas fa-paperclip"></i> Attached File:</strong></p>
                                    <a href="../<?php echo htmlspecialchars($homework_details['file_path']); ?>" download="<?php echo htmlspecialchars($homework_details['file_name'] ?? 'file'); ?>" class="homework-file">
                                        <i class="fas fa-paperclip"></i>
                                        <?php echo htmlspecialchars($homework_details['file_name'] ?? 'Download File'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <a href="homework.php" class="btn btn-secondary">Close</a>
                            <?php if (!empty($homework_details['file_path'])): ?>
                                <a href="../<?php echo htmlspecialchars($homework_details['file_path']); ?>" download="<?php echo htmlspecialchars($homework_details['file_name'] ?? 'file'); ?>" class="btn btn-primary">
                                    <i class="fas fa-download"></i> Download Attached File
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Active Homework Section -->
        <div class="mb-5">
            <h3 class="section-title"><i class="fas fa-clock"></i> Active Home Work (<?php echo $homeworks ? $homeworks->num_rows : 0; ?>)</h3>
            
            <?php if ($homeworks && $homeworks->num_rows > 0): ?>
                <div class="homework-grid">
                    <?php while ($hw = $homeworks->fetch_assoc()): ?>
                        <div class="homework-card">
                            <div class="homework-header">
                                <h5 class="homework-title"><?php echo htmlspecialchars($hw['title']); ?></h5>
                                <span class="homework-date"><i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($hw['created_at'])); ?></span>
                            </div>
                            <div class="homework-meta">
                                <span><i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($hw['teacher_name'] ?? 'N/A'); ?></span>
                                <span><i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($hw['class_name']); ?></span>
                            </div>
                            <div class="homework-details">
                                <?php echo nl2br(htmlspecialchars(mb_substr($hw['details'], 0, 150))); ?>
                                <?php if (mb_strlen($hw['details']) > 150): ?>...<?php endif; ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <?php if ($hw['file_path'] && file_exists($hw['file_path'])): ?>
                                    <a href="<?php echo htmlspecialchars($hw['file_path']); ?>" download="<?php echo htmlspecialchars($hw['file_name']); ?>" class="homework-file" onclick="event.stopPropagation();">
                                        <i class="fas fa-paperclip"></i> Download Attached File
                                    </a>
                                <?php else: ?>
                                    <span></span>
                                <?php endif; ?>
                                <a href="homework.php?view=<?php echo $hw['id']; ?>" class="btn-view">
                                    View <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <p>No active homework. Great job!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Completed Homework Section -->
        <div>
            <h3 class="section-title"><i class="fas fa-check-double"></i> Completed Home Work (<?php echo $completed_homeworks ? $completed_homeworks->num_rows : 0; ?>)</h3>
            
            <?php if ($completed_homeworks && $completed_homeworks->num_rows > 0): ?>
                <div class="homework-grid">
                    <?php while ($hw = $completed_homeworks->fetch_assoc()): ?>
                        <div class="homework-card completed">
                            <div class="homework-header">
                                <h5 class="homework-title"><?php echo htmlspecialchars($hw['title']); ?></h5>
                                <span class="homework-date"><i class="far fa-calendar-check"></i> <?php echo date('d M Y', strtotime($hw['updated_at'])); ?></span>
                            </div>
                            <div class="homework-meta">
                                <span><i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($hw['teacher_name'] ?? 'N/A'); ?></span>
                                <span><i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($hw['class_name']); ?></span>
                            </div>
                            <div class="homework-details">
                                <?php echo nl2br(htmlspecialchars(mb_substr($hw['details'], 0, 100))); ?>
                                <?php if (mb_strlen($hw['details']) > 100): ?>...<?php endif; ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <?php if ($hw['file_path'] && file_exists($hw['file_path'])): ?>
                                    <a href="<?php echo htmlspecialchars($hw['file_path']); ?>" download="<?php echo htmlspecialchars($hw['file_name']); ?>" class="homework-file" onclick="event.stopPropagation();">
                                        <i class="fas fa-paperclip"></i> Download Attached File
                                    </a>
                                <?php else: ?>
                                    <span></span>
                                <?php endif; ?>
                                <a href="homework.php?view=<?php echo $hw['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                    View <i class="fas fa-eye ms-1"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <p>No completed homework yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const toggle = document.getElementById('sidebarToggle');
            
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            toggle.classList.toggle('toggle-active');
        }
        
        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            toggleSidebar();
        });
        
        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'student_logout.php';
            }
        }
        
        function openPasswordModal() {
            alert('Password change functionality');
        }
    </script>
</body>
</html>

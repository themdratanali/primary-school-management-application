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
$dynamicId = isset($_SESSION['student_dynamic_id']) ? (int)$_SESSION['student_dynamic_id'] : 0;

if (!$dynamicId) {
    die("Student not found.");
}

// Fee type categories and colors
$fee_type_categories = [
    'monthly' => 'Monthly Fee',
    'exam' => 'Exam Fee',
    'admission' => 'Admission Fee',
    'transport' => 'Transport Fee',
    'sport' => 'Sport Fee',
    'study tour fee' => 'Study Tour Fee',
];

$category_colors = [
    'monthly' => '#3498db',
    'exam' => '#e74c3c',
    'admission' => '#27ae60',
    'transport' => '#f39c12',
    'sport' => '#9b59b6',
    'study tour fee' => '#1abc9c',
];

// Initialize variables
$fees = [];
$total_paid = 0.0;
$fees_by_category = [];

// Get student batch and class from session
$batch_id = $_SESSION['student_batch_id'] ?? null;
$class_id = $_SESSION['student_class_id'] ?? null;
$student_name = $_SESSION['student_name'] ?? '';
$student_roll = $_SESSION['student_roll'] ?? '';

$batch_name = '';
$class_name = '';

// Fetch batch name
if ($batch_id) {
    $bStmt = $conn->prepare("SELECT name FROM batches WHERE id = ? LIMIT 1");
    if ($bStmt) {
        $bStmt->bind_param("i", $batch_id);
        $bStmt->execute();
        $bStmt->bind_result($batch_name);
        $bStmt->fetch();
        $bStmt->close();
    }
}

if ($class_id) {
    $cStmt = $conn->prepare("SELECT name FROM classes WHERE id = ? LIMIT 1");
    if ($cStmt) {
        $cStmt->bind_param("i", $class_id);
        $cStmt->execute();
        $cStmt->bind_result($class_name);
        $cStmt->fetch();
        $cStmt->close();
    }
}

// Fetch fees from dynamic fee tables for current batch and class
if ($batch_name && $class_name) {
    $batch_slug = strtolower(str_replace(' ', '_', $batch_name));
    $class_slug = strtolower(str_replace(' ', '_', $class_name));

    $table_like = "fees_" . $batch_slug . "_" . $class_slug . "_%";
    $tables_res = $conn->query("SHOW TABLES LIKE '$table_like'");
    if ($tables_res) {
        while ($table_row = $tables_res->fetch_array()) {
            $table_name = $table_row[0];
            $fee_res = $conn->query("SELECT * FROM `$table_name` WHERE student_id = " . (int)$dynamicId . " ORDER BY created_at DESC");
            if ($fee_res) {
                while ($row = $fee_res->fetch_assoc()) {
                    $fees[] = $row;
                    $total_paid += (float)($row['amount'] ?? 0);
                }
            }
        }
    }
}

foreach ($fees as $fee) {
    $catRaw = $fee['fee_type_category'] ?? '';
    $catKey = strtolower($catRaw);
    if (!isset($fees_by_category[$catKey])) {
        $label = $fee_type_categories[$catKey] ?? ucfirst($catRaw);
        $color = $category_colors[$catKey] ?? '#667eea';
        $fees_by_category[$catKey] = [
            'label' => $label,
            'total' => 0.0,
            'count' => 0,
            'color' => $color,
        ];
    }
    $fees_by_category[$catKey]['total'] += (float)($fee['amount'] ?? 0);
    $fees_by_category[$catKey]['count']++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Summary - Student Dashboard</title>
    <link rel="shortcut icon" type="image/jpg" href="<?php echo BASE_URL; ?>/uploads/images/এ্যাপেক্স মডেল স্কুল.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/dashboard.css">
    <style>
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #177a03 0%, #145c02 100%);
            color: white;
            padding: 20px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(23, 122, 3, 0.3);
        }
        .page-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .page-header h2 i {
            margin-right: 10px;
        }
        
        /* Mobile Bottom Navigation */
        .mobile-bottom-nav {
            display: none;
        }
        @media (max-width: 768px) {
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
            .main-content {
                padding-bottom: 80px !important;
            }
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
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
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        /* Mobile modal centering */
        @media (max-width: 768px) {
            .modal-content {
                margin: 50% auto !important;
                width: 85% !important;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="sidebar" id="sidebar">
        <h4>Apex Model School</h4>
        <a href="<?php echo ams_student_url('dashboard'); ?>">
            <i class="fas fa-id-badge"></i> <span>Profile</span>
        </a>
        <a href="<?php echo ams_student_url('results'); ?>">
            <i class="fas fa-clipboard-list"></i> <span>Results</span>
        </a>
        <a href="<?php echo ams_student_url('fees'); ?>" class="active">
            <i class="fas fa-coins"></i> <span>Fee Summary</span>
        </a>
        <div class="logout-link">
            <a href="student_logout">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h2><i class="fas fa-coins"></i> Fee Summary</h2>
        </div>
        <div class="container-fluid">
            <!-- Fee Summary Section -->
            <div class="card-section">
                <h4><i class="fas fa-chart-pie"></i> Fee Summary (<?= htmlspecialchars($batch_name . ' - ' . $class_name) ?>)</h4>
                <?php if (!empty($fees_by_category)): ?>
                    <div class="fee-card-row">
                        <?php foreach ($fees_by_category as $cat => $data): ?>
                            <div class="fee-category-card" style="border-top: 4px solid <?= htmlspecialchars($data['color']) ?>;">
                                <div class="icon-wrapper" style="background: <?= htmlspecialchars($data['color']) ?>;">
                                    <i class="fas fa-tag"></i>
                                </div>
                                <div class="fee-label"><?= htmlspecialchars($data['label']) ?></div>
                                <div class="fee-amount">৳ <?= number_format($data['total'], 2) ?></div>
                                <div class="fee-count"><?= (int)$data['count'] ?> payment(s)</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        No fee records found for your account in this class.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Payment History Section -->
            <?php if (!empty($fees)): ?>
            <div class="card-section">
                <h4><i class="fas fa-history"></i> Payment History</h4>
                <div class="table-responsive">
                    <table class="fee-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Details</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fees as $fee): ?>
                                <tr>
                                    <td><?= htmlspecialchars($fee['fee_type_category'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($fee['fee_type_detail'] ?? '-') ?></td>
                                    <td><span class="amount-value">৳ <?= number_format((float)($fee['amount'] ?? 0), 2) ?></span></td>
                                    <td><?= htmlspecialchars(isset($fee['created_at']) ? date('d M, Y', strtotime($fee['created_at'])) : '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/library/js/myscript.js"></script>
    <script>
    // Enhanced sidebar toggle with animation - same as admin/dashboard
    document.addEventListener('DOMContentLoaded', function() {
        var sidebarToggle = document.getElementById('sidebarToggle');
        var sidebar = document.getElementById('sidebar');
        var sidebarOverlay = document.getElementById('sidebarOverlay');
        
        if (!sidebarToggle || !sidebar) return;
        
        // Define toggleSidebar function globally
        window.toggleSidebar = function() {
            sidebar.classList.toggle('active');
            sidebarToggle.classList.toggle('toggle-active');
            sidebarOverlay.classList.toggle('active');
            
            // Add class to body only on mobile
            if (window.innerWidth <= 768) {
                document.body.classList.toggle('sidebar-open');
            }
            
            // Save state to localStorage
            var isActive = sidebar.classList.contains('active');
            localStorage.setItem('sidebarState', isActive ? 'open' : 'closed');
        };
        
        // Close on overlay click
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', toggleSidebar);
        }
        
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('active')) {
                toggleSidebar();
            }
        });
        
        // Restore state only on mobile
        if (window.innerWidth <= 768 && localStorage.getItem('sidebarState') === 'open') {
            sidebar.classList.add('active');
            sidebarToggle.classList.add('toggle-active');
            sidebarOverlay.classList.add('active');
            document.body.classList.add('sidebar-open');
        }
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                sidebarToggle.classList.remove('toggle-active');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.remove('active');
                }
            }
        });
    });
    </script>
    
    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav">
        <a href="<?php echo ams_student_url('dashboard'); ?>">
            <i class="fas fa-id-badge"></i>
            <span>Profile</span>
        </a>
        <a href="<?php echo ams_student_url('results'); ?>">
            <i class="fas fa-clipboard-list"></i>
            <span>Results</span>
        </a>
        <a href="<?php echo ams_student_url('marksheet'); ?>">
            <i class="fas fa-book"></i>
            <span>Mark Sheet</span>
        </a>
        <a href="<?php echo ams_student_url('fees'); ?>" class="active">
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
    
    <!-- Change Password Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div style="background: linear-gradient(135deg, #177a03 0%, #219a04 100%); color: white; padding: 15px; border-radius: 10px 10px 0 0; margin: -20px -20px 20px -20px;">
                <span class="close" onclick="closePasswordModal()" style="color: white; float: right; cursor: pointer;">&times;</span>
                <h3 style="margin: 0;"><i class="fas fa-key me-2"></i>Change Password</h3>
            </div>
            <div style="padding: 20px;">
                <input type="hidden" id="studentUserId" value="<?= $student_user_id ?>">
                <div class="mb-3">
                    <label for="studentNewPassword" class="form-label">New Password</label>
                    <input type="text" class="form-control" id="studentNewPassword" placeholder="Enter new password">
                </div>
                <div id="studentPasswordMessage" style="margin-top: 10px;"></div>
                <div style="margin-top: 15px; text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveStudentPassword()" style="background-color: #177a03; border-color: #177a03;">
                        <i class="fas fa-save me-1"></i>Save
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function openPasswordModal() {
        document.getElementById('passwordModal').style.display = 'block';
        document.getElementById('studentPasswordMessage').innerHTML = '';
        document.getElementById('studentNewPassword').value = '';
    }
    
    function closePasswordModal() {
        document.getElementById('passwordModal').style.display = 'none';
    }
    
    function confirmLogout() {
        var result = confirm('Are you sure you want to logout?');
        if (result) {
            window.location.href = 'student_logout';
        }
    }
    
    function saveStudentPassword() {
        var userId = document.getElementById('studentUserId').value;
        var newPassword = document.getElementById('studentNewPassword').value.trim();
        var messageDiv = document.getElementById('studentPasswordMessage');
        
        if (!newPassword) {
            messageDiv.innerHTML = '<div class="alert alert-danger">Please enter a new password</div>';
            return;
        }
        
        messageDiv.innerHTML = '<div class="alert alert-info">Processing...</div>';
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo ams_admin_url('update_student_password'); ?>', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        messageDiv.innerHTML = '<div class="alert alert-success">Password updated successfully!</div>';
                        setTimeout(function() {
                            closePasswordModal();
                        }, 1500);
                    } else {
                        messageDiv.innerHTML = '<div class="alert alert-danger">' + response.message + '</div>';
                    }
                } catch (e) {
                    messageDiv.innerHTML = '<div class="alert alert-danger">An error occurred</div>';
                }
            }
        };
        xhr.send('user_id=' + encodeURIComponent(userId) + '&password=' + encodeURIComponent(newPassword));
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        var modal = document.getElementById('passwordModal');
        if (event.target == modal) {
            closePasswordModal();
        }
    }
    </script>
</body>
</html>












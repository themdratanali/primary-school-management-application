<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['student_email'])) {
    header('Location: login.php');
    exit;
}

$email = $_SESSION['student_email'];
$dynamicId = isset($_SESSION['student_dynamic_id']) ? (int)$_SESSION['student_dynamic_id'] : 0;

if (!$dynamicId) {
    die("Student not found.");
}

// Get the main student ID from students table for results query
$mainStudentId = $_SESSION['student_main_id'] ?? 0;
if (!$mainStudentId) {
    $mapStmt = $conn->prepare("
        SELECT s.id 
        FROM student_users su
        JOIN students s ON s.id = su.student_id
        WHERE su.email = ? 
        LIMIT 1
    ");
    if ($mapStmt) {
        $mapStmt->bind_param("s", $email);
        $mapStmt->execute();
        $mapRes = $mapStmt->get_result();
        if ($mapRes && $mapRes->num_rows > 0) {
            $mapRow = $mapRes->fetch_assoc();
            $mainStudentId = (int)$mapRow['id'];
            $_SESSION['student_main_id'] = $mainStudentId;
        }
        $mapStmt->close();
    }

    // If no mapping found, try to get from student_users.student_id directly
    if (!$mainStudentId) {
        $directStmt = $conn->prepare("SELECT student_id FROM student_users WHERE email = ? LIMIT 1");
        if ($directStmt) {
            $directStmt->bind_param("s", $email);
            $directStmt->execute();
            $directRes = $directStmt->get_result();
            if ($directRes && $directRes->num_rows > 0) {
                $directRow = $directRes->fetch_assoc();
                $mainStudentId = (int)$directRow['student_id'];
                $_SESSION['student_main_id'] = $mainStudentId;
            }
            $directStmt->close();
        }
    }
}

// Get student's current class from session
$student_class_id = $_SESSION['student_class_id'] ?? 0;
$student_batch_id = $_SESSION['student_batch_id'] ?? 0;

// Get student's current class name
$current_class_name = '';
$current_class_stmt = $conn->prepare("SELECT name FROM classes WHERE id = ? LIMIT 1");
if ($current_class_stmt && $student_class_id > 0) {
    $current_class_stmt->bind_param("i", $student_class_id);
    $current_class_stmt->execute();
    $current_class_stmt->bind_result($current_class_name);
    $current_class_stmt->fetch();
    $current_class_stmt->close();
}

// Get all classes in the student's history (including all batches after promotion)
$available_classes = [];
$class_options = [];

// Search ALL batch tables for classes the student belongs to
$allBatchesRes = $conn->query("SELECT id, name FROM batches ORDER BY name");
if ($allBatchesRes) {
    while ($batchRow = $allBatchesRes->fetch_assoc()) {
        $batchSlug = strtolower(str_replace(' ', '_', $batchRow['name']));
        $batchId = (int)$batchRow['id'];
        
        // Search ALL student tables in this batch for the student
        $tablesRes = $conn->query("SHOW TABLES LIKE 'Student_{$batchSlug}_%'");
        if ($tablesRes) {
            while ($tblRow = $tablesRes->fetch_row()) {
                $tableName = $tblRow[0];
                $classCheckStmt = $conn->prepare("SELECT DISTINCT class_id FROM `$tableName` WHERE id = ?");
                if ($classCheckStmt) {
                    $classCheckStmt->bind_param("i", $dynamicId);
                    $classCheckStmt->execute();
                    $classCheckRes = $classCheckStmt->get_result();
                    while ($row = $classCheckRes->fetch_assoc()) {
                        if (!empty($row['class_id'])) {
                            $classId = (int)$row['class_id'];
                            $key = $batchId . '_' . $classId;
                            if (!isset($available_classes[$key])) {
                                // Get class name
                                $classNameStmt = $conn->prepare("SELECT name FROM classes WHERE id = ? LIMIT 1");
                                if ($classNameStmt) {
                                    $classNameStmt->bind_param("i", $classId);
                                    $classNameStmt->execute();
                                    $classNameRes = $classNameStmt->get_result();
                                    if ($classNameRes && $classNameRes->num_rows > 0) {
                                        $classNameRow = $classNameRes->fetch_assoc();
                                        $available_classes[$key] = [
                                            'batch_id' => $batchId,
                                            'batch_name' => $batchRow['name'],
                                            'class_id' => $classId,
                                            'class_name' => $classNameRow['name'],
                                            'is_current' => ($batchId === $student_batch_id && $classId === $student_class_id)
                                        ];
                                        
                                        // Build option label
                                        $label = $batchRow['name'] . ' - ' . $classNameRow['name'];
                                        $class_options[$key] = [
                                            'id' => $classId,
                                            'batch_id' => $batchId,
                                            'name' => $label
                                        ];
                                    }
                                    $classNameStmt->close();
                                }
                            }
                        }
                    }
                    $classCheckStmt->close();
                }
            }
        }
    }
}

// Get selected class from query param
$selected_results_class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$selected_batch_id = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;

// Default to current class and batch
if ($selected_results_class_id === 0) {
    $selected_results_class_id = $student_class_id;
}
if ($selected_batch_id === 0) {
    $selected_batch_id = $student_batch_id;
}

// Get batch year and class name for selected
$batch_year = '';
$selected_class_name = '';

if ($selected_batch_id > 0) {
    $batchYearStmt = $conn->prepare("SELECT name FROM batches WHERE id = ? LIMIT 1");
    if ($batchYearStmt) {
        $batchYearStmt->bind_param("i", $selected_batch_id);
        $batchYearStmt->execute();
        $batchYearStmt->bind_result($batch_year);
        $batchYearStmt->fetch();
        $batchYearStmt->close();
    }
}

if ($selected_results_class_id > 0) {
    $classNameStmt = $conn->prepare("SELECT name FROM classes WHERE id = ? LIMIT 1");
    if ($classNameStmt) {
        $classNameStmt->bind_param("i", $selected_results_class_id);
        $classNameStmt->execute();
        $classNameStmt->bind_result($selected_class_name);
        $classNameStmt->fetch();
        $classNameStmt->close();
    }
}

// Query dynamic results tables
$exam_types = ['1st Tutorial', '2nd Tutorial', '3rd Tutorial', '1st Term Exam', '2nd Term Exam', 'Annual Exam'];

$results_by_exam = [];
$class_name_lower = strtolower(str_replace(' ', '_', $selected_class_name));

foreach ($exam_types as $exam_type) {
    $result_type_clean = strtolower(str_replace(' ', '_', $exam_type));
    $table_name = "results_{$batch_year}_{$class_name_lower}_{$result_type_clean}";
    
    // Check if table exists
    $tableExistsRes = $conn->query("SHOW TABLES LIKE '$table_name'");
    if ($tableExistsRes && $tableExistsRes->num_rows > 0) {
        // Query results from dynamic table
        $dynamicStmt = $conn->prepare("
            SELECT r.subject_id, r.marks, r.exam_type
            FROM `$table_name` r
            WHERE r.student_id = ? AND r.class_id = ?
        ");
        if ($dynamicStmt) {
            $dynamicStmt->bind_param("ii", $mainStudentId, $selected_results_class_id);
            $dynamicStmt->execute();
            $dynamicRes = $dynamicStmt->get_result();
            
            if ($dynamicRes && $dynamicRes->num_rows > 0) {
                while ($row = $dynamicRes->fetch_assoc()) {
                    // Get subject name
                    $subjectId = $row['subject_id'];
                    $subjectName = '';
                    $subjectStmt = $conn->prepare("SELECT name FROM subjects WHERE id = ? LIMIT 1");
                    if ($subjectStmt) {
                        $subjectStmt->bind_param("i", $subjectId);
                        $subjectStmt->execute();
                        $subjectStmt->bind_result($subjectName);
                        $subjectStmt->fetch();
                        $subjectStmt->close();
                    }
                    
                    if (!isset($results_by_exam[$exam_type])) {
                        $results_by_exam[$exam_type] = [];
                    }
                    $results_by_exam[$exam_type][] = [
                        'subject_name' => $subjectName ?: 'Subject',
                        'marks' => $row['marks']
                    ];
                }
            }
            $dynamicStmt->close();
        }
    }
}

// Get student info
$student_name = $_SESSION['student_name'] ?? '';
$student_roll = $_SESSION['student_roll'] ?? '';

// Get batch name
$batch_name = 'N/A';
if ($student_batch_id > 0) {
    $batch_stmt = $conn->prepare("SELECT name FROM batches WHERE id = ? LIMIT 1");
    if ($batch_stmt) {
        $batch_stmt->bind_param("i", $student_batch_id);
        $batch_stmt->execute();
        $batch_stmt->bind_result($batch_name);
        $batch_stmt->fetch();
        $batch_stmt->close();
    }
}

// Get class name
$class_name = 'N/A';
if ($student_class_id > 0) {
    $class_stmt = $conn->prepare("SELECT name FROM classes WHERE id = ? LIMIT 1");
    if ($class_stmt) {
        $class_stmt->bind_param("i", $student_class_id);
        $class_stmt->execute();
        $class_stmt->bind_result($class_name);
        $class_stmt->fetch();
        $class_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Results - Student Dashboard</title>
    <link rel="shortcut icon" type="image/jpg" href="../assets/img/এ্যাপেক্স মডেল স্কুল.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/apex/assets/fontawesome/fontawesome-free-6.4.0-web/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
        <a href="dashboard.php">
            <i class="fas fa-id-badge"></i> <span>Profile</span>
        </a>
        <a href="student_results.php" class="active">
            <i class="fas fa-clipboard-list"></i> <span>Results</span>
        </a>
        <a href="homework.php">
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
        <div class="page-header">
            <h2><i class="fas fa-clipboard-list"></i> My Results</h2>
        </div>
        <div class="container-fluid">
            <!-- Select Class Section -->
            <div class="card-section">
                <h4><i class="fas fa-search"></i> Select Class</h4>
                <div class="row">
                    <div>
                        <div class="class-select-box">
                            <label>Choose Class to View Results</label>
                            <select name="class_id" class="form-select" onchange="window.location.href='student_results.php?batch_id=' + this.options[this.selectedIndex].getAttribute('data-batch') + '&class_id=' + this.value">
                                <?php if (empty($class_options)): ?>
                                    <option value="">No classes available</option>
                                <?php else: ?>
                                    <?php foreach ($class_options as $opt): ?>
                                        <option value="<?= (int)$opt['id'] ?>" data-batch="<?= (int)$opt['batch_id'] ?>"
                                            <?= ((int)$opt['id'] === $selected_results_class_id && (int)$opt['batch_id'] === $selected_batch_id) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($opt['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Section -->
            <?php if ($selected_results_class_id): ?>
                <div class="card-section">
                    <h4><i class="fas fa-list-alt"></i> Results for <?= htmlspecialchars($selected_class_name) ?></h4>
                    <?php if (!empty($results_by_exam)): ?>
                        <?php foreach ($results_by_exam as $exam_type => $rows): ?>
                            <div class="result-card">
                                <div class="card-header">
                                    <h5><i class="fas fa-file-alt"></i> <?= htmlspecialchars($exam_type) ?></h5>
                                </div>
                                <div class="card-body">
                                    <table class="exam-table">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th style="text-align:center;">Marks</th>
                                                <th style="text-align:center;">Total</th>
                                                <th style="text-align:center;">Grade</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_marks = 0;
                                            $obtained_marks = 0;
                                            foreach ($rows as $r): 
                                                $total = 100; // Default total marks
                                                $marks = (int)$r['marks'];
                                                $total_marks += $total;
                                                $obtained_marks += $marks;
                                                $percentage = ($marks / $total) * 100;
                                                
                                                if ($percentage >= 90) $grade = 'A+';
                                                elseif ($percentage >= 80) $grade = 'A';
                                                elseif ($percentage >= 70) $grade = 'B';
                                                elseif ($percentage >= 60) $grade = 'C';
                                                elseif ($percentage >= 33) $grade = 'D';
                                                else $grade = 'F';
                                            ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($r['subject_name']) ?></td>
                                                    <td class="marks-cell"><?= htmlspecialchars($r['marks']) ?></td>
                                                    <td class="total-cell"><?= $total ?></td>
                                                    <td class="grade-cell grade-<?= str_replace('+', '-plus', $grade) ?>"><?= $grade ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr style="background: #f8f9fa; font-weight: 700;">
                                                <td>Total</td>
                                                <td class="marks-cell"><?= $obtained_marks ?></td>
                                                <td class="total-cell"><?= $total_marks ?></td>
                                                <td class="grade-cell">
                                                    <?php 
                                                        $overall_percentage = ($obtained_marks / $total_marks) * 100;
                                                        if ($overall_percentage >= 90) echo '<span class="grade-A-plus">A+</span>';
                                                        elseif ($overall_percentage >= 80) echo '<span class="grade-A">A</span>';
                                                        elseif ($overall_percentage >= 70) echo '<span class="grade-B">B</span>';
                                                        elseif ($overall_percentage >= 60) echo '<span class="grade-C">C</span>';
                                                        elseif ($overall_percentage >= 33) echo '<span class="grade-D">D</span>';
                                                        else echo '<span class="grade-F">F</span>';
                                                    ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Results Not Available</strong><br>
                            Results for <strong><?= htmlspecialchars($selected_class_name) ?></strong> have not been published yet. Please check back later.
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-graduation-cap"></i>
                    <p>No results have been recorded for your account yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="../assets/js/myScript.js"></script>
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
        <a href="dashboard.php">
            <i class="fas fa-id-badge"></i>
            <span>Profile</span>
        </a>
        <a href="student_results.php" class="active">
            <i class="fas fa-clipboard-list"></i>
            <span>Results</span>
        </a>
        <a href="homework.php">
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
            window.location.href = 'student_logout.php';
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
        xhr.open('POST', '../admin/update_student_password.php', true);
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

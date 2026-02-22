<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

// Allow both admin and logged-in student
if (!isset($_SESSION['admin']) && !isset($_SESSION['student_email'])) {
    header('Location: login.php');
    exit;
}

// For students, get parameters from session or redirect
$is_student = isset($_SESSION['student_email']) && !isset($_SESSION['admin']);
if ($is_student) {
    $batch_id = $_SESSION['student_batch_id'] ?? null;
    $class_id = $_SESSION['student_class_id'] ?? null;
    $student_id = $_SESSION['student_dynamic_id'] ?? null;
    
    if (!$batch_id || !$class_id || !$student_id) {
        die("Student information not found. Please login again.");
    }
}

$conn->set_charset("utf8mb4");

/* ------------------ Fee Type Categories ------------------ */
$fee_type_categories = [
    'monthly' => 'Monthly Fee',
    'exam' => 'Exam Fee',
    'admission' => 'Admission Fee',
    'transport' => 'Transport Fee',
    'sport' => 'Sport Fee',
    'study tour fee' => 'Study Tour Fee'
];

/* ------------------ Category Colors ------------------ */
$category_colors = [
    'monthly' => '#3498db',
    'exam' => '#e74c3c',
    'admission' => '#27ae60',
    'transport' => '#f39c12',
    'sport' => '#9b59b6',
    'study tour fee' => '#1abc9c'
];

function getReadableCategory($category)
{
    global $fee_type_categories;
    $category_lower = strtolower($category);
    return isset($fee_type_categories[$category_lower]) ? $fee_type_categories[$category_lower] : ucfirst($category);
}

function getCategoryColor($category)
{
    global $category_colors;
    $category_lower = strtolower($category);
    return isset($category_colors[$category_lower]) ? $category_colors[$category_lower] : '#667eea';
}

if ($is_student) {
    // Already have values from session
    $batch_id = (int)$batch_id;
    $class_id = (int)$class_id;
    $student_id = (int)$student_id;
} elseif (isset($_GET['batch_id'], $_GET['class_id'], $_GET['student_id'])) {
    $batch_id = intval($_GET['batch_id']);
    $class_id = intval($_GET['class_id']);
    $student_id = intval($_GET['student_id']);
} else {
    $batch_id = 0;
    $class_id = 0;
    $student_id = 0;
}

// Initialize variables
$fees = [];
$total_paid = 0.0;
$student_name = '';
$student_roll = '';
$student_photo = '';
$student_batch = '';
$student_class = '';

// Group fees by category for summary
$fees_by_category = [];

if ($batch_id > 0 && $class_id > 0 && $student_id > 0) {
    $batch_name_res = $conn->query("SELECT name FROM batches WHERE id = $batch_id");
    $batch_data = $batch_name_res->num_rows ? $batch_name_res->fetch_assoc() : null;
    $batch_name = $batch_data ? strtolower(str_replace(' ', '_', $batch_data['name'])) : '';
    $batch_name_display = $batch_data ? $batch_data['name'] : '';

    $class_name_res = $conn->query("SELECT name FROM classes WHERE id = $class_id");
    $class_data = $class_name_res->num_rows ? $class_name_res->fetch_assoc() : null;
    $class_name = $class_data ? strtolower(str_replace(' ', '_', $class_data['name'])) : '';
    $class_name_display = $class_data ? $class_data['name'] : '';

    $student_table = "Student_" . $batch_name . "_" . $class_name;
    $result_table = $conn->query("SHOW TABLES LIKE '$student_table'");
    
    if ($result_table && $result_table->num_rows) {
        $student_res = $conn->query("SELECT name, roll, photo FROM `$student_table` WHERE id = $student_id");
        if ($student_res->num_rows) {
            $student_data = $student_res->fetch_assoc();
            $student_name = $student_data['name'];
            $student_roll = $student_data['roll'];
            $student_photo = $student_data['photo'];
            $student_batch = $batch_name_display;
            $student_class = $class_name_display;
        }

        // Collect fees from all fee tables
        $table_like = "fees_" . $batch_name . "_" . $class_name . "_%";
        $tables_res = $conn->query("SHOW TABLES LIKE '$table_like'");
        
        $fees = [];
        $total_paid = 0;
        
        while ($table_row = $tables_res->fetch_array()) {
            $table_name = $table_row[0];
            $fee_res = $conn->query("SELECT * FROM `$table_name` WHERE student_id = $student_id ORDER BY created_at DESC");
            while ($row = $fee_res->fetch_assoc()) {
                $fees[] = $row;
                $total_paid += (float)$row['amount'];
            }
        }
    }

    // Group fees by category for summary
    $fees_by_category = [];
    
    foreach ($fees as $fee) {
        $cat = strtolower($fee['fee_type_category']);
        if (!isset($fees_by_category[$cat])) {
            $fees_by_category[$cat] = [
                'label' => getReadableCategory($fee['fee_type_category']),
                'total' => 0,
                'count' => 0,
                'color' => getCategoryColor($fee['fee_type_category'])
            ];
        }
        $fees_by_category[$cat]['total'] += (float)$fee['amount'];
        $fees_by_category[$cat]['count']++;
    }
}

// For dropdowns (admin only)
$batches = [];
$classes = [];
if (!$is_student) {
    $res_batches = $conn->query("SELECT id, name FROM batches ORDER BY name DESC");
    while ($row = $res_batches->fetch_assoc()) {
        $batches[] = $row;
    }

    $res_classes = $conn->query("SELECT id, name FROM classes ORDER BY name");
    while ($row = $res_classes->fetch_assoc()) {
        $classes[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>View Student Fee Records - Apex Model School</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/fee_see.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/apex/assets/fontawesome/fontawesome-free-6.4.0-web/css/all.min.css">
    
    <style>
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
            .container {
                padding-bottom: 80px !important;
            }
        }
        
        /* Change Password Modal */
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
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: #000;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .btn-submit {
            background: linear-gradient(135deg, #177a03 0%, #219a04 100%);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #145a02 0%, #177a03 100%);
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
    <div class="container">
        <?php if ($is_student): ?>
            <?php if ($student_name): ?>
            <!-- Student Profile Card for Students -->
            <div class="student-profile-card">
                <div class="profile-header">
                    <div class="profile-photo-section">
                        <?php if ($student_photo && file_exists($student_photo)): ?>
                            <img src="<?= htmlspecialchars($student_photo) ?>" alt="Student Photo" class="profile-photo">
                        <?php else: ?>
                            <div class="profile-photo-placeholder">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h3><?= htmlspecialchars($student_name) ?></h3>
                        <div class="info-badges">
                            <span class="badge-item"><i class="fas fa-id-badge"></i> Roll: <?= htmlspecialchars($student_roll ?? 'N/A') ?></span>
                            <span class="badge-item"><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($student_batch) ?></span>
                            <span class="badge-item"><i class="fas fa-door-open"></i> Class: <?= htmlspecialchars($student_class) ?></span>
                        </div>
                    </div>
                    <div class="profile-summary">
                        <div class="summary-card total">
                            <div class="summary-icon"><i class="fas fa-wallet"></i></div>
                            <div class="summary-content">
                                <span class="summary-value">৳ <?= number_format($total_paid, 2) ?></span>
                                <span class="summary-label">Total Paid</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fee Summary by Category -->
            <?php if (!empty($fees_by_category)): ?>
            <div class="fee-summary-section">
                <h4><i class="fas fa-chart-pie"></i> Fee Summary by Category</h4>
                <div class="summary-grid">
                    <?php foreach ($fees_by_category as $cat => $data): ?>
                        <div class="summary-card" style="--card-color: <?= $data['color'] ?>">
                            <div class="card-icon"><i class="fas fa-tag"></i></div>
                            <div class="card-content">
                                <span class="card-value">৳ <?= number_format($data['total'], 2) ?></span>
                                <span class="card-label"><?= htmlspecialchars($data['label']) ?></span>
                                <span class="card-count"><?= $data['count'] ?> transaction(s)</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <p style="color:#666;">Fee information not available.</p>
            <?php endif; ?>
        <?php else: ?>
        <!-- Admin view with search form -->
        <form method="get" class="fee-search-form">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Academic Batch</label>
                    <select name="batch_id" class="form-select select2" required>
                        <option value="">-- Select Batch --</option>
                        <?php foreach ($batches as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= (isset($batch_id) && $batch_id == $b['id']) ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Class</label>
                    <select name="class_id" class="form-select select2" required>
                        <option value="">-- Select Class --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (isset($class_id) && $class_id == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Student</label>
                    <select name="student_id" id="student_id" class="form-select select2" required>
                        <option value="">-- Select Student --</option>
                        <?php if (isset($student_name) && $student_name): ?>
                            <option value="<?= $student_id ?>" selected><?= htmlspecialchars($student_name) ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
        </form>

        <?php if ($student_name): ?>
            <!-- Student Profile Card -->
            <div class="student-profile-card">
                <div class="profile-header">
                    <div class="profile-photo-section">
                        <?php if ($student_photo && file_exists($student_photo)): ?>
                            <img src="<?= htmlspecialchars($student_photo) ?>" alt="Student Photo" class="profile-photo">
                        <?php else: ?>
                            <div class="profile-photo-placeholder">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h3><?= htmlspecialchars($student_name) ?></h3>
                        <div class="info-badges">
                            <span class="badge-item"><i class="fas fa-id-badge"></i> Roll: <?= htmlspecialchars($student_roll ?? 'N/A') ?></span>
                            <span class="badge-item"><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($student_batch) ?></span>
                            <span class="badge-item"><i class="fas fa-door-open"></i> Class: <?= htmlspecialchars($student_class) ?></span>
                        </div>
                    </div>
                    <div class="profile-summary">
                        <div class="summary-card total">
                            <div class="summary-icon"><i class="fas fa-wallet"></i></div>
                            <div class="summary-content">
                                <span class="summary-value">৳ <?= number_format($total_paid, 2) ?></span>
                                <span class="summary-label">Total Paid</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fee Summary by Category -->
            <?php if (!empty($fees_by_category)): ?>
            <div class="fee-summary-section">
                <h4><i class="fas fa-chart-pie"></i> Fee Summary by Category</h4>
                <div class="summary-grid">
                    <?php foreach ($fees_by_category as $cat => $data): ?>
                        <div class="summary-card" style="--card-color: <?= $data['color'] ?>">
                            <div class="card-icon"><i class="fas fa-tag"></i></div>
                            <div class="card-content">
                                <span class="card-value">৳ <?= number_format($data['total'], 2) ?></span>
                                <span class="card-label"><?= htmlspecialchars($data['label']) ?></span>
                                <span class="card-count"><?= $data['count'] ?> transaction(s)</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Fee Records Table -->
            <?php if (!empty($fees)): ?>
                <div class="fee-table-section">
                    <div class="table-header">
                        <h4><i class="fas fa-history"></i> Payment History</h4>
                        <?php if (!$is_student): ?>
                        <div class="table-actions">
                            <button class="btn btn-sm btn-outline-primary" onclick="exportToCSV()">
                                <i class="fas fa-file-csv"></i> Export CSV
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table fee-table" id="feeTable">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag"></i> SL</th>
                                    <th><i class="fas fa-tag"></i> Category</th>
                                    <th><i class="fas fa-info-circle"></i> Details</th>
                                    <th><i class="fas fa-money-bill-wave"></i> Amount</th>
                                    <th><i class="fas fa-calendar-alt"></i> Payment Date</th>
                                    <?php if (!$is_student): ?>
                                    <th><i class="fas fa-cog"></i> Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fees as $i => $fee): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td>
                                            <span class="category-badge" style="background: <?= getCategoryColor($fee['fee_type_category']) ?>">
                                                <?= htmlspecialchars(getReadableCategory($fee['fee_type_category'] ?? '-')) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($fee['fee_type_detail'] ?? '-') ?></td>
                                        <td class="amount-cell"><strong>৳ <?= number_format((float)($fee['amount'] ?? 0), 2) ?></strong></td>
                                        <td><?= htmlspecialchars(date('d M, Y h:i A', strtotime($fee['created_at'] ?? 'now'))) ?></td>
                                        <?php if (!$is_student): ?>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info" onclick="viewReceipt('<?= htmlspecialchars($fee['receipt_id'] ?? '') ?>')" title="View Receipt">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total Amount</strong></td>
                                    <td class="amount-cell"><strong>৳ <?= number_format($total_paid, 2) ?></strong></td>
                                    <td colspan="<?= $is_student ? 1 : 2 ?>"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-receipt"></i></div>
                    <h3>No Fee Records Found</h3>
                    <p>No payment records found for your account.</p>
                </div>
            <?php endif; ?>
        <?php elseif (isset($_GET['student_id'])): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-user-times"></i></div>
                <h3>Student Not Found</h3>
                <p>No student found with the selected criteria. Please check your batch, class, and student selection.</p>
            </div>
        <?php else: ?>
            <div class="welcome-state">
                <div class="welcome-icon"><i class="fas fa-search-dollar"></i></div>
                <h3>Search for Student Fee Records</h3>
                <p>Select an academic batch, class, and student to view their fee payment history and records.</p>
            </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(function() {
            // Initialize Select2
            $('.select2').select2({
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true
            });

            function loadStudents(batchId, classId, selectedStudentId = null) {
                if (!batchId || !classId) {
                    $('#student_id').html('<option value="">-- Select Student --</option>');
                    return;
                }
                $.post('get_students_by_batch_class.php', {
                    batch_id: batchId,
                    class_id: classId
                }, function(data) {
                    $('#student_id').html(data);
                    if (selectedStudentId) {
                        $('#student_id').val(selectedStudentId).trigger('change');
                    }
                });
            }

            $('select[name="batch_id"], select[name="class_id"]').on('change', function() {
                loadStudents($('select[name="batch_id"]').val(), $('select[name="class_id"]').val());
            });

            <?php if (isset($batch_id, $class_id, $student_id)): ?>
                loadStudents(<?= $batch_id ?>, <?= $class_id ?>, <?= $student_id ?>);
            <?php endif; ?>
        });

        function exportToCSV() {
            const table = document.getElementById('feeTable');
            let csv = [];
            
            // Get headers
            const headers = [];
            table.querySelectorAll('thead th').forEach((th, index) => {
                if (index < 6) { // Exclude Actions column
                    headers.push(th.innerText.replace(' ', '_'));
                }
            });
            csv.push(headers.join(','));

            // Get data
            table.querySelectorAll('tbody tr').forEach(row => {
                const rowData = [];
                row.querySelectorAll('td').forEach((td, index) => {
                    if (index < 6) { // Exclude Actions column
                        rowData.push('"' + td.innerText.replace(/"/g, '""') + '"');
                    }
                });
                csv.push(rowData.join(','));
            });

            // Download
            const csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
            const downloadLink = document.createElement('a');
            downloadLink.download = 'fee_records_<?= date('Ymd') ?>.csv';
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }

        function viewReceipt(receiptId) {
            // Implement receipt view functionality
            alert('Receipt ID: ' + receiptId);
        }
    </script>
    
    <!-- Mobile Bottom Navigation (only for students) -->
    <?php if ($is_student): ?>
    <div class="mobile-bottom-nav">
        <a href="dashboard.php">
            <i class="fas fa-id-badge"></i>
            <span>Profile</span>
        </a>
        <a href="student_results.php">
            <i class="fas fa-clipboard-list"></i>
            <span>Results</span>
        </a>
        <a href="homework.php">
            <i class="fas fa-book"></i>
            <span>Home Work</span>
        </a>
        <a href="fee_see.php" class="active">
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
        <div class="modal-content">
            <span class="close" onclick="closePasswordModal()">&times;</span>
            <h3><i class="fas fa-key me-2"></i>Change Password</h3>
            <form id="passwordForm" onsubmit="return changePassword(event)">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn-submit">Change Password</button>
            </form>
        </div>
    </div>
    
    <script>
        function openPasswordModal() {
            document.getElementById('passwordModal').style.display = 'block';
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
        
        function changePassword(event) {
            event.preventDefault();
            
            var newPassword = document.getElementById('new_password').value;
            var confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                alert('New password and confirm password do not match!');
                return false;
            }
            
            // For students, we need to get their table and id from session
            // This is a simplified version - in production you'd have a proper API
            alert('Password change functionality would connect to update_student_password.php');
            closePasswordModal();
            return false;
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('passwordModal');
            if (event.target == modal) {
                closePasswordModal();
            }
        }
    </script>
    <?php endif; ?>
</body>

</html>

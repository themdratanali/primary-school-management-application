<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

/* ------------------ Admin check ------------------ */
if (!isset($_SESSION['admin'])) {
    header('Location: ../admin/login.php');
    exit;
}

/* ------------------ Get Current Month ------------------ */
$currentMonthIndex = date('n') - 1; // 0-11 for January-December
$months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
$currentMonth = $months[$currentMonthIndex];

/* ------------------ Fee Type Categories ------------------ */
$fee_type_categories = [
    'Monthly' => [
        'label' => 'Monthly Fee',
        'is_monthly' => true,
        'options' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
    ],
    'Exam' => [
        'label' => 'Exam Fee',
        'is_monthly' => false,
        'has_details' => true,
        'options' => ['1st Tutorial', '2nd Tutorial', '3rd Tutorial', '1st Term Exam', '2nd Term Exam', 'Annual Exam']
    ],
    'Admission' => [
        'label' => 'Admission Fee',
        'is_monthly' => false,
        'has_details' => false,
        'options' => ['New Admission']
    ],
    'Transport' => [
        'label' => 'Transport Fee',
        'is_monthly' => false,
        'has_details' => false,
        'options' => ['Transport Fee']
    ],
    'Sport' => [
        'label' => 'Sport Fee',
        'is_monthly' => false,
        'has_details' => false,
        'options' => ['Sport Fee']
    ],
    'Study Tour Fee' => [
        'label' => 'Study Tour Fee',
        'is_monthly' => false,
        'has_details' => false,
        'options' => ['Study Tour Fee']
    ]
];

/* ------------------ Fetch Batches & Classes ------------------ */
$batches = $conn->query("SELECT * FROM batches ORDER BY name");
$classes = $conn->query("SELECT * FROM classes ORDER BY name");

/* ------------------ Get Request Params ------------------ */
$batch_id = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$category = isset($_GET['category']) ? $_GET['category'] : 'Monthly';
$month = isset($_GET['month']) ? $_GET['month'] : $currentMonth;
$fee_detail = isset($_GET['fee_detail']) ? $_GET['fee_detail'] : '';

/* ------------------ Get Batch & Class Name ------------------ */
$batch_name = '';
$class_name = '';
if ($batch_id > 0) {
    $stmt = $conn->prepare("SELECT name FROM batches WHERE id = ?");
    $stmt->bind_param("i", $batch_id);
    $stmt->execute();
    $stmt->bind_result($batch_name);
    $stmt->fetch();
    $stmt->close();
}
if ($class_id > 0) {
    $stmt = $conn->prepare("SELECT name FROM classes WHERE id = ?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $stmt->bind_result($class_name);
    $stmt->fetch();
    $stmt->close();
}

/* ------------------ Helper Functions ------------------ */
function sanitize_table_part($str) {
    return preg_replace('/[^a-zA-Z0-9]/', '_', trim($str));
}

function get_fee_table_name($batch_name, $class_name, $category) {
    $batch_clean = sanitize_table_part($batch_name);
    $class_clean = sanitize_table_part($class_name);
    return "fees_" . $batch_clean . "_" . $class_clean . "_" . strtolower($category);
}

function get_all_fee_tables($conn, $batch_clean, $class_clean) {
    $tables = [];
    $all_tables = $conn->query("SHOW TABLES");
    while ($table = $all_tables->fetch_array()) {
        $table_name = $table[0];
        if (stripos($table_name, $batch_clean) !== false && 
            stripos($table_name, $class_clean) !== false && 
            stripos($table_name, 'fees_') !== false) {
            $tables[] = $table_name;
        }
    }
    return $tables;
}

/* ------------------ Get Students ------------------ */
$students = [];
$student_table = '';
if ($batch_id > 0 && $class_id > 0) {
    $batch_clean = sanitize_table_part($batch_name);
    $class_clean = sanitize_table_part($class_name);
    $student_table = "Student_{$batch_clean}_{$class_clean}";
    
    $result = $conn->query("SHOW TABLES LIKE '$student_table'");
    if ($result && $result->num_rows > 0) {
        $sql = "SELECT id, name, roll FROM `$student_table` ORDER BY roll ASC";
        $students_result = $conn->query($sql);
        while ($row = $students_result->fetch_assoc()) {
            $students[] = $row;
        }
    }
}

/* ------------------ Get Paid Records ------------------ */
$paid_students = [];
$paid_details = [];
if ($batch_id > 0 && $class_id > 0 && !empty($students)) {
    $batch_clean = sanitize_table_part($batch_name);
    $class_clean = sanitize_table_part($class_name);
    
    // Get all fee tables for this batch/class
    $fee_tables = get_all_fee_tables($conn, $batch_clean, $class_clean);
    
    if ($fee_type_categories[$category]['is_monthly']) {
        // For monthly fees, check specific month
        foreach ($fee_tables as $table_name) {
            $result = $conn->query("SELECT DISTINCT student_id, fee_type_detail FROM `$table_name` WHERE LOWER(fee_type_category) = 'monthly' AND LOWER(fee_type_detail) = '" . strtolower($month) . "'");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $paid_students[$row['student_id']] = true;
                    if (!isset($paid_details[$row['student_id']])) {
                        $paid_details[$row['student_id']] = [];
                    }
                    $paid_details[$row['student_id']][] = $row['fee_type_detail'];
                }
            }
        }
    } elseif (!empty($fee_detail)) {
        // For categories with specific details (like Exam)
        foreach ($fee_tables as $table_name) {
            $result = $conn->query("SELECT DISTINCT student_id, fee_type_detail FROM `$table_name` WHERE LOWER(fee_type_category) = '" . strtolower($category) . "' AND LOWER(fee_type_detail) = '" . strtolower($fee_detail) . "'");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $paid_students[$row['student_id']] = true;
                    if (!isset($paid_details[$row['student_id']])) {
                        $paid_details[$row['student_id']] = [];
                    }
                    $paid_details[$row['student_id']][] = $row['fee_type_detail'];
                }
            }
        }
    } else {
        // For other fee types without specific details
        $table_name = get_fee_table_name($batch_name, $class_name, $category);
        $result = $conn->query("SHOW TABLES LIKE '$table_name'");
        if ($result && $result->num_rows > 0) {
            $result = $conn->query("SELECT DISTINCT student_id, fee_type_detail FROM `$table_name`");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $paid_students[$row['student_id']] = true;
                    if (!isset($paid_details[$row['student_id']])) {
                        $paid_details[$row['student_id']] = [];
                    }
                    $paid_details[$row['student_id']][] = $row['fee_type_detail'];
                }
            }
        }
    }
}

/* ------------------ Separate Paid and Unpaid Students ------------------ */
$paid_list = [];
$unpaid_list = [];
foreach ($students as $stu) {
    if (isset($paid_students[$stu['id']])) {
        $stu['paid_details'] = $paid_details[$stu['id']] ?? [];
        $paid_list[] = $stu;
    } else {
        $stu['paid_details'] = [];
        $unpaid_list[] = $stu;
    }
}

/* ------------------ Stats ------------------ */
$total_students = count($students);
$paid_count = count($paid_list);
$unpaid_count = count($unpaid_list);
$paid_percentage = $total_students > 0 ? round(($paid_count / $total_students) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Fee Status - Apex Model School</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="/apex/assets/fontawesome/fontawesome-free-6.4.0-web/css/all.min.css">
    <style>
        .fee-status-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            background: linear-gradient(135deg, #177a03, #145a02);
            color: white;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(23, 122, 3, 0.3);
        }
        
        .page-header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        
        .page-header p {
            margin: 0;
            opacity: 0.9;
        }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-form .form-group {
            flex: 1;
            min-width: 150px;
        }
        
        .filter-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .filter-form select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            background: #f8f9fa;
        }
        
        .filter-form button {
            padding: 10px 25px;
            background: #177a03;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .filter-form button:hover {
            background: #145a02;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .stat-card.total::before {
            background: #6c757d;
        }
        
        .stat-card.paid::before {
            background: #28a745;
        }
        
        .stat-card.unpaid::before {
            background: #dc3545;
        }
        
        .stat-card.percentage::before {
            background: #177a03;
        }
        
        .stat-card .icon {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .stat-card.total .icon {
            color: #6c757d;
        }
        
        .stat-card.paid .icon {
            color: #28a745;
        }
        
        .stat-card.unpaid .icon {
            color: #dc3545;
        }
        
        .stat-card.percentage .icon {
            color: #177a03;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: 700;
            color: #333;
        }
        
        .stat-card .label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        /* Progress Bar */
        .progress-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .progress-header h3 {
            margin: 0;
            color: #333;
        }
        
        .progress-bar-container {
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            display: flex;
        }
        
        .progress-bar-paid {
            background: linear-gradient(90deg, #28a745, #20c997);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 12px;
            transition: width 0.5s ease;
        }
        
        .progress-bar-unpaid {
            background: #dc3545;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 12px;
            transition: width 0.5s ease;
        }
        
        /* Data Tables */
        .data-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        @media (max-width: 1000px) {
            .data-section {
                grid-template-columns: 1fr;
            }
        }
        
        .data-box {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .data-box-header {
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .data-box-header.paid {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .data-box-header.unpaid {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .data-box-header h3 {
            margin: 0;
            font-size: 16px;
        }
        
        .data-box-header .badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .data-table .roll {
            width: 60px;
            color: #666;
        }
        
        .data-table .name {
            font-weight: 500;
        }
        
        .data-table .status {
            width: 80px;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-badge.paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.unpaid {
            background: #f8d7da;
            color: #721c24;
        }
        
        .empty-message {
            padding: 40px;
            text-align: center;
            color: #666;
        }
        
        .empty-message i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Scrollable table */
        .table-scroll {
            max-height: 400px;
            overflow-y: auto;
        }
        
        /* Category Tabs */
        .category-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .category-tab {
            padding: 10px 20px;
            background: #f8f9fa;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .category-tab:hover {
            border-color: #177a03;
            color: #177a03;
        }
        
        .category-tab.active {
            background: #177a03;
            border-color: #177a03;
            color: white;
        }
        
        /* Month Selector */
        .month-selector {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        
        .month-btn {
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }
        
        .month-btn:hover {
            border-color: #177a03;
        }
        
        .month-btn.active {
            background: #177a03;
            border-color: #177a03;
            color: white;
        }
    </style>
</head>

<body>    
    <div class="fee-status-container">
        <div class="filter-section">
            <form method="get" class="filter-form">
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Batch</label>
                    <select name="batch_id" onchange="this.form.submit()">
                        <option value="0">-- Select Batch --</option>
                        <?php while ($batch = $batches->fetch_assoc()): ?>
                            <option value="<?= $batch['id'] ?>" <?= $batch_id == $batch['id'] ? 'selected' : '' ?>><?= htmlspecialchars($batch['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-graduation-cap"></i> Class</label>
                    <select name="class_id" onchange="this.form.submit()">
                        <option value="0">-- Select Class --</option>
                        <?php while ($class = $classes->fetch_assoc()): ?>
                            <option value="<?= $class['id'] ?>" <?= $class_id == $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Fee Category</label>
                    <select name="category" id="categorySelect" onchange="updateFeeDetails(); this.form.submit()">
                        <?php foreach ($fee_type_categories as $key => $cat): ?>
                            <option value="<?= $key ?>" <?= $category == $key ? 'selected' : '' ?>><?= $cat['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" id="monthGroup" style="flex: 2;">
                    <label><i class="fas fa-calendar"></i> Month</label>
                    <select name="month" onchange="this.form.submit()">
                        <?php foreach ($months as $m): ?>
                            <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" id="feeDetailGroup" style="flex: 2;">
                    <label><i class="fas fa-list-alt"></i> Fee Type Detail</label>
                    <select name="fee_detail" id="feeDetailSelect" onchange="this.form.submit()">
                        <option value="">-- Select Detail --</option>
                    </select>
                </div>
            </form>
        </div>
        
        <script>
            const feeTypeCategories = <?php echo json_encode($fee_type_categories); ?>;
            const months = <?php echo json_encode($months); ?>;
            
            function updateFeeDetails() {
                const category = document.getElementById('categorySelect').value;
                const monthGroup = document.getElementById('monthGroup');
                const feeDetailGroup = document.getElementById('feeDetailGroup');
                const feeDetailSelect = document.getElementById('feeDetailSelect');
                
                const cat = feeTypeCategories[category];
                
                // Show/hide month dropdown
                if (cat && cat.is_monthly) {
                    monthGroup.style.display = 'block';
                } else {
                    monthGroup.style.display = 'none';
                }
                
                // Show/hide fee detail dropdown
                if (cat && cat.has_details && cat.options && cat.options.length > 0) {
                    feeDetailGroup.style.display = 'block';
                    feeDetailSelect.innerHTML = '<option value="">-- Select Detail --</option>';
                    cat.options.forEach(opt => {
                        feeDetailSelect.innerHTML += '<option value="' + opt + '">' + opt + '</option>';
                    });
                } else {
                    feeDetailGroup.style.display = 'none';
                    feeDetailSelect.innerHTML = '<option value="">-- Select Detail --</option>';
                }
            }
            
            // Initialize on page load
            document.addEventListener('DOMContentLoaded', function() {
                // Hide both groups initially
                document.getElementById('monthGroup').style.display = 'none';
                document.getElementById('feeDetailGroup').style.display = 'none';
                
                // Then update based on selected category
                updateFeeDetails();
                
                // Set selected fee detail if exists
                const selectedDetail = '<?php echo htmlspecialchars($fee_detail); ?>';
                if (selectedDetail) {
                    document.getElementById('feeDetailSelect').value = selectedDetail;
                }
            });
        </script>
        
        <?php if ($batch_id > 0 && $class_id > 0): ?>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="icon"><i class="fas fa-users"></i></div>
                <div class="number"><?= $total_students ?></div>
                <div class="label">Total Students</div>
            </div>
            
            <div class="stat-card paid">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <div class="number"><?= $paid_count ?></div>
                <div class="label">Paid Students</div>
            </div>
            
            <div class="stat-card unpaid">
                <div class="icon"><i class="fas fa-times-circle"></i></div>
                <div class="number"><?= $unpaid_count ?></div>
                <div class="label">Unpaid Students</div>
            </div>
            
            <div class="stat-card percentage">
                <div class="icon"><i class="fas fa-chart-pie"></i></div>
                <div class="number"><?= $paid_percentage ?>%</div>
                <div class="label">Payment Rate</div>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="progress-section">
            <div class="progress-header">
                <h3><i class="fas fa-chart-bar"></i> Payment Progress 
                    <?php
                    $status_label = htmlspecialchars($fee_type_categories[$category]['label']);
                    if ($fee_type_categories[$category]['is_monthly']) {
                        $status_label .= ' - ' . htmlspecialchars($month);
                    } elseif (!empty($fee_detail)) {
                        $status_label .= ' - ' . htmlspecialchars($fee_detail);
                    }
                    echo $status_label;
                    ?>
                </h3>
                <span><?= $paid_count ?> of <?= $total_students ?> students paid</span>
            </div>
            <div class="progress-bar-container">
                <?php if ($total_students > 0): ?>
                <div class="progress-bar-paid" style="width: <?= $paid_percentage ?>%">
                    <?php if ($paid_percentage > 15): ?><?= $paid_percentage ?>%<?php endif; ?>
                </div>
                <div class="progress-bar-unpaid" style="width: <?= 100 - $paid_percentage ?>%">
                    <?php if ($paid_percentage < 85): ?><?= 100 - $paid_percentage ?>%<?php endif; ?>
                </div>
                <?php else: ?>
                <div style="width: 100%; padding: 10px; text-align: center; color: #666;">
                    No students found
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Data Tables -->
        <div class="data-section">
            <!-- Paid Students Box -->
            <div class="data-box">
                <div class="data-box-header paid">
                    <h3><i class="fas fa-check"></i> Paid Students</h3>
                    <span class="badge"><?= $paid_count ?></span>
                </div>
                <?php if (!empty($paid_list)): ?>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="roll">Roll</th>
                                <th class="name">Student Name</th>
                                <th class="status">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paid_list as $stu): ?>
                            <tr>
                                <td class="roll"><?= htmlspecialchars($stu['roll'] ?? 'N/A') ?></td>
                                <td class="name"><?= htmlspecialchars($stu['name']) ?></td>
                                <td class="status">
                                    <span class="status-badge paid">
                                        <i class="fas fa-check"></i> Paid
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-message">
                    <i class="fas fa-check-circle"></i>
                    <p>No students have paid yet</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Unpaid Students Box -->
            <div class="data-box">
                <div class="data-box-header unpaid">
                    <h3><i class="fas fa-exclamation-triangle"></i> Unpaid Students</h3>
                    <span class="badge"><?= $unpaid_count ?></span>
                </div>
                <?php if (!empty($unpaid_list)): ?>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="roll">Roll</th>
                                <th class="name">Student Name</th>
                                <th class="status">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unpaid_list as $stu): ?>
                            <tr>
                                <td class="roll"><?= htmlspecialchars($stu['roll'] ?? 'N/A') ?></td>
                                <td class="name"><?= htmlspecialchars($stu['name']) ?></td>
                                <td class="status">
                                    <span class="status-badge unpaid">
                                        <i class="fas fa-times"></i> Unpaid
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-message">
                    <i class="fas fa-party-popper"></i>
                    <p>All students have paid!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php else: ?>
        
        <div class="empty-message" style="background: white; border-radius: 12px; padding: 60px;">
            <i class="fas fa-filter" style="font-size: 64px; color: #ddd;"></i>
            <h3>Select Batch and Class</h3>
            <p>Choose a batch and class above to view fee status</p>
        </div>
        
        <?php endif; ?>
    </div>
</body>
</html>

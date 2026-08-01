<?php
require_once __DIR__ . '/../../env/session.php';
include '../../env/config.php';

// Auth check
if (!isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

$page_title = "Fee Overview - Apex Model School";

// Helper function
function sanitize_table_part($str)
{
    return preg_replace('/[^a-zA-Z0-9]/', '_', trim($str));
}

// Get all batches and classes
$batches = $conn->query("SELECT id, name FROM batches ORDER BY name");
$classes = $conn->query("SELECT id, name FROM classes ORDER BY name");
$batch_id = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Get batch and class names
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

// Date ranges for fee calculations
$thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
$threeSixtyFiveDaysAgo = date('Y-m-d H:i:s', strtotime('-365 days'));

// Get all fee tables for summary
$all_fee_tables = [];
$table_results = $conn->query("SHOW TABLES LIKE 'fees_%'");
while ($table = $table_results->fetch_array()) {
    $all_fee_tables[] = $table[0];
}

// Calculate 30-day total income
$total_30_days = 0;
foreach ($all_fee_tables as $table) {
    $stmt = $conn->prepare("SELECT SUM(CAST(amount AS DECIMAL(10,2))) as total FROM `$table` WHERE created_at >= ?");
    $stmt->bind_param('s', $thirtyDaysAgo);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $total_30_days += floatval($row['total'] ?? 0);
    }
    $stmt->close();
}

// Calculate 365-day total income
$total_365_days = 0;
foreach ($all_fee_tables as $table) {
    $stmt = $conn->prepare("SELECT SUM(CAST(amount AS DECIMAL(10,2))) as total FROM `$table` WHERE created_at >= ?");
    $stmt->bind_param('s', $threeSixtyFiveDaysAgo);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $total_365_days += floatval($row['total'] ?? 0);
    }
    $stmt->close();
}

// Class-wise statistics
$class_stats = [];
$student_list = [];
if ($batch_id > 0 && $class_id > 0) {
    $batch_clean = sanitize_table_part($batch_name);
    $class_clean = sanitize_table_part($class_name);
    $student_table = "Student_{$batch_clean}_{$class_clean}";
    
    // Check if student table exists
    $check_table = $conn->query("SHOW TABLES LIKE '$student_table'");
    if ($check_table && $check_table->num_rows > 0) {
        // Get all students
        $total_students = 0;
        $students_result = $conn->query("SELECT id, name, roll FROM `$student_table` ORDER BY roll ASC");
        
        // Get paid student IDs in last 30 days
        $paid_students_30 = [];
        foreach ($all_fee_tables as $table) {
            if (stripos($table, "fees_{$batch_clean}_{$class_clean}") !== false) {
                $stmt = $conn->prepare("SELECT DISTINCT student_id FROM `$table` WHERE created_at >= ?");
                $stmt->bind_param('s', $thirtyDaysAgo);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $paid_students_30[$row['student_id']] = true;
                }
                $stmt->close();
            }
        }
        
        while ($row = $students_result->fetch_assoc()) {
            $total_students++;
            $student_list[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'roll' => $row['roll'],
                'paid' => isset($paid_students_30[$row['id']])
            ];
        }
        
        $paid_count_30 = count($paid_students_30);
        $unpaid_count_30 = $total_students - $paid_count_30;
        
        $class_stats = [
            'total_students' => $total_students,
            'paid_30_days' => $paid_count_30,
            'unpaid_30_days' => $unpaid_count_30,
            'paid_percentage' => $total_students > 0 ? round(($paid_count_30 / $total_students) * 100, 1) : 0
        ];
    }
}

// Overall class statistics (all batches/classes)
$overall_stats = [];
$all_students = 0;
$all_paid_30 = [];
foreach ($all_fee_tables as $table) {
    $stmt = $conn->prepare("SELECT DISTINCT student_id FROM `$table` WHERE created_at >= ?");
    $stmt->bind_param('s', $thirtyDaysAgo);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_paid_30[$row['student_id']] = true;
    }
    $stmt->close();
}

// Count total students across all dynamic tables
$student_tables = [];
$tables_result = $conn->query("SHOW TABLES LIKE 'Student_%'");
while ($table = $tables_result->fetch_array()) {
    $student_tables[] = $table[0];
}
foreach ($student_tables as $table) {
    $count_result = $conn->query("SELECT COUNT(*) as cnt FROM `$table`");
    if ($row = $count_result->fetch_assoc()) {
        $all_students += intval($row['cnt']);
    }
}
$all_paid_count = count($all_paid_30);
$all_unpaid_count = $all_students - $all_paid_count;

?>
<link rel="stylesheet" href="<?= BASE_URL ?>/library/fontawesome/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/library/css/overview_fee.css">

<div class="main-content">
    <div class="content-wrapper">
        <div class="stats-grid">
            <div class="stat-card income">
                <div class="icon"><i class="fas fa-calendar-day"></i></div>
                <div class="number">৳<?= number_format($total_30_days, 0) ?></div>
                <div class="label">30 Days Total Income</div>
            </div>
            <div class="stat-card income">
                <div class="icon"><i class="fas fa-calendar"></i></div>
                <div class="number">৳<?= number_format($total_365_days, 0) ?></div>
                <div class="label">365 Days Total Income</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-users"></i></div>
                <div class="number"><?= $all_students ?></div>
                <div class="label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <div class="number"><?= $all_paid_count ?></div>
                <div class="label">Students Paid (30 Days)</div>
            </div>
        </div>

        <div class="filter-section">
            <form method="get" class="search-form">
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Batch</label>
                    <select name="batch_id" class="form-select" onchange="this.form.submit()">
                        <option value="0">-- All Batches --</option>
                        <?php while ($batch = $batches->fetch_assoc()): ?>
                            <option value="<?= $batch['id'] ?>" <?= $batch_id == $batch['id'] ? 'selected' : '' ?>><?= htmlspecialchars($batch['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-graduation-cap"></i> Class</label>
                    <select name="class_id" class="form-select" onchange="this.form.submit()">
                        <option value="0">-- All Classes --</option>
                        <?php while ($class = $classes->fetch_assoc()): ?>
                            <option value="<?= $class['id'] ?>" <?= $class_id == $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($batch_id > 0 && $class_id > 0 && !empty($class_stats)): ?>
        <div class="section-header">
            <h3>Class-wise Statistics (<?= htmlspecialchars($class_name) ?>)</h3>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-users"></i></div>
                <div class="number"><?= $class_stats['total_students'] ?></div>
                <div class="label">Total Students</div>
            </div>
            <div class="stat-card paid">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <div class="number"><?= $class_stats['paid_30_days'] ?></div>
                <div class="label">Paid (30 Days)</div>
            </div>
            <div class="stat-card unpaid">
                <div class="icon"><i class="fas fa-times-circle"></i></div>
                <div class="number"><?= $class_stats['unpaid_30_days'] ?></div>
                <div class="label">Unpaid (30 Days)</div>
            </div>
        </div>
        <div class="progress-section">
            <div class="progress-header">
                <span>Payment Progress: <?= $class_stats['paid_percentage'] ?>%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $class_stats['paid_percentage'] ?>%">
                    <?= $class_stats['paid_percentage'] ?>% Paid
                </div>
            </div>
        </div>

        <div class="section-header">
            <h3><i class="fas fa-list"></i> Student List</h3>
        </div>
        <div class="student-list-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Roll</th>
                            <th>Student Name</th>
                            <th>Status (30 Days)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($student_list as $stu): ?>
                        <tr>
                            <td data-label="Roll"><?= htmlspecialchars($stu['roll'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($stu['name']) ?></td>
                            <td>
                                <?php if ($stu['paid']): ?>
                                    <span class="badge badge-paid"><i class="fas fa-check-circle"></i> Paid</span>
                                <?php else: ?>
                                    <span class="badge badge-unpaid"><i class="fas fa-times-circle"></i> Unpaid</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="student-list-footer">
                <span>Showing <strong><?= count($student_list) ?></strong> students</span>
                <div class="summary-pills">
                    <span class="pill pill-paid">
                        <span class="pill-dot"></span>
                        Paid: <?= $class_stats['paid_30_days'] ?? 0 ?>
                    </span>
                    <span class="pill pill-unpaid">
                        <span class="pill-dot"></span>
                        Unpaid: <?= $class_stats['unpaid_30_days'] ?? 0 ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
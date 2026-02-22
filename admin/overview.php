<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$total_students = 0;
$batches_all = $conn->query("SELECT * FROM batches ORDER BY name");
$classes_all = $conn->query("SELECT * FROM classes ORDER BY name");

$students_by_class = [];
$chart_data = [];

while ($batch = $batches_all->fetch_assoc()) {
    $classes_all->data_seek(0);
    while ($class = $classes_all->fetch_assoc()) {
        $batch_name_clean = preg_replace('/\s+/', '', $batch['name']);
        $class_name_clean = preg_replace('/\s+/', '', $class['name']);
        $table_name = "Student_{$batch_name_clean}_{$class_name_clean}";
        
        $check_table = $conn->query("SHOW TABLES LIKE '$table_name'");
        if ($check_table && $check_table->num_rows > 0) {
            $count = $conn->query("SELECT COUNT(*) as total FROM `$table_name`")->fetch_assoc()['total'];
            $total_students += $count;
            if ($count > 0) {
                $label = $batch['name'] . ' - ' . $class['name'];
                $students_by_class[] = [
                    'batch_name' => $batch['name'],
                    'class_name' => $class['name'],
                    'count' => $count,
                    'table_name' => $table_name,
                    'label' => $label
                ];
                $chart_data[] = [
                    'label' => $label,
                    'value' => $count
                ];
            }
        }
    }
}

$batch_count = $conn->query("SELECT COUNT(*) as total FROM batches")->fetch_assoc()['total'];
$class_count = $conn->query("SELECT COUNT(*) as total FROM classes")->fetch_assoc()['total'];
$teacher_count = $conn->query("SELECT COUNT(*) as total FROM teachers")->fetch_assoc()['total'];

$total_fees = 0;

// Count Total Staff from staff table
$staff_count = $conn->query("SELECT COUNT(*) as total FROM staff")->fetch_assoc()['total'];

// Count Admins
$admin_count = $conn->query("SELECT COUNT(*) as total FROM admins")->fetch_assoc()['total'];

// Year Admission Students - count students admitted in current year
$current_year = date('Y');
$year_admission_students = 0;
$batches_res = $conn->query("SELECT name FROM batches");
$classes_res = $conn->query("SELECT name FROM classes");

$batches = [];
$classes = [];

while ($row = $batches_res->fetch_assoc()) {
  $batches[] = strtolower(str_replace(' ', '_', $row['name']));
}

while ($row = $classes_res->fetch_assoc()) {
  $classes[] = strtolower(str_replace(' ', '_', $row['name']));
}

foreach ($batches as $batch) {
  foreach ($classes as $class) {
    $like_pattern = "fees_{$batch}_{$class}_%";
    $tables_res = $conn->query("SHOW TABLES LIKE '$like_pattern'");
    while ($table_row = $tables_res->fetch_array()) {
      $table_name = $table_row[0];
      $sum_res = $conn->query("SELECT SUM(CAST(amount AS DECIMAL(10,2))) as sum_amount FROM `$table_name`");
      if ($sum_res && $sum_res->num_rows) {
        $sum = $sum_res->fetch_assoc()['sum_amount'];
        $total_fees += (float)($sum ?? 0);
      }
    }
  }
}

// Count year admission students (based on batch name containing current year)
$year_admission_students = 0;
$current_year_batch = $conn->query("SELECT * FROM batches WHERE name LIKE '%$current_year%'");
if ($current_year_batch && $current_year_batch->num_rows > 0) {
    while ($batch = $current_year_batch->fetch_assoc()) {
        $classes_all->data_seek(0);
        while ($class = $classes_all->fetch_assoc()) {
            $batch_name_clean = preg_replace('/\s+/', '', $batch['name']);
            $class_name_clean = preg_replace('/\s+/', '', $class['name']);
            $table_name = "Student_{$batch_name_clean}_{$class_name_clean}";
            $check_table = $conn->query("SHOW TABLES LIKE '$table_name'");
            if ($check_table && $check_table->num_rows > 0) {
                $count = $conn->query("SELECT COUNT(*) as total FROM `$table_name`")->fetch_assoc()['total'];
                $year_admission_students += $count;
            }
        }
    }
}

// GPA Distribution Data - Calculate grades from results
$gpa_data = [
    'A+' => 0,
    'A' => 0,
    'A-' => 0,
    'B' => 0,
    'C' => 0,
    'D' => 0,
    'F' => 0
];

// Check if results table has data with marks
$results_check = $conn->query("SELECT marks FROM results WHERE marks IS NOT NULL AND marks != '' LIMIT 1");
if ($results_check && $results_check->num_rows > 0) {
    $all_results = $conn->query("SELECT marks FROM results WHERE marks IS NOT NULL AND marks != ''");
    while ($result = $all_results->fetch_assoc()) {
        $marks = floatval($result['marks']);
        if ($marks >= 90) $gpa_data['A+']++;
        elseif ($marks >= 85) $gpa_data['A']++;
        elseif ($marks >= 80) $gpa_data['A-']++;
        elseif ($marks >= 70) $gpa_data['B']++;
        elseif ($marks >= 60) $gpa_data['C']++;
        elseif ($marks >= 40) $gpa_data['D']++;
        else $gpa_data['F']++;
    }
}

// Promoted Students Count - Estimate by comparing batch years
$promoted_students = 0;
$all_batches = $conn->query("SELECT * FROM batches ORDER BY name");
$all_classes = $conn->query("SELECT * FROM classes ORDER BY name");

$batch_years = [];
while ($b = $all_batches->fetch_assoc()) {
    // Extract year from batch name (e.g., "2021 One" -> 2021)
    if (preg_match('/(\d{4})/', $b['name'], $matches)) {
        $batch_years[$b['id']] = [
            'name' => $b['name'],
            'year' => intval($matches[1])
        ];
    }
}

// For each batch that is not the earliest, count students as potentially promoted
if (count($batch_years) > 1) {
    $min_year = min(array_column($batch_years, 'year'));
    foreach ($batch_years as $batch_id => $batch_info) {
        if ($batch_info['year'] > $min_year) {
            $all_classes->data_seek(0);
            while ($class = $all_classes->fetch_assoc()) {
                $batch_name_clean = preg_replace('/\s+/', '', $batch_info['name']);
                $class_name_clean = preg_replace('/\s+/', '', $class['name']);
                $table_name = "Student_{$batch_name_clean}_{$class_name_clean}";
                $check_table = $conn->query("SHOW TABLES LIKE '$table_name'");
                if ($check_table && $check_table->num_rows > 0) {
                    $count = $conn->query("SELECT COUNT(*) as total FROM `$table_name`")->fetch_assoc()['total'];
                    $promoted_students += $count;
                }
            }
        }
    }
}

$chart_labels = json_encode(array_column($chart_data, 'label'));
$chart_values = json_encode(array_column($chart_data, 'value'));

$gpa_labels = json_encode(array_keys($gpa_data));
$gpa_values = json_encode(array_values($gpa_data));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Overview - Dashboard</title>
  <link rel="shortcut icon" type="image/jpg" href="../assets/img/এ্যাপেক্স মডেল স্কুল.png"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="/apex/assets/fontawesome/fontawesome-free-6.4.0-web/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background: #f5f7fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 25px;
    }
    .stat-card {
      background: linear-gradient(135deg, #177a03 0%, #145a02 100%);
      border-radius: 12px;
      padding: 24px 20px;
      color: white;
      transition: transform 0.2s, box-shadow 0.2s;
      position: relative;
      overflow: hidden;
    }
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(23, 122, 3, 0.3);
    }
    .stat-card h5 {
      font-size: 14px;
      opacity: 0.95;
      margin-bottom: 10px;
      font-weight: 500;
      color: white;
    }
    .stat-card h2 {
      font-size: 32px;
      font-weight: 700;
      margin: 0;
      color: white;
    }
    .stat-icon {
      position: absolute;
      right: 15px;
      bottom: 15px;
      font-size: 48px;
      opacity: 0.15;
    }
    .chart-container {
      background: white;
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      margin: 15px;
    }
    .chart-container h3 {
      margin-bottom: 20px;
      color: #333;
      font-size: 18px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .chart-container h3 i {
      color: #177a03;
    }
    .students-by-class {
      background: white;
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      margin: 15px;
    }
    .students-by-class h3 {
      margin-bottom: 20px;
      color: #333;
      font-size: 18px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .students-by-class h3 i {
      color: #177a03;
    }
    .class-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 14px 16px;
      border-bottom: 1px solid #f0f0f0;
      transition: background 0.2s;
    }
    .class-item:hover {
      background: #f8f9fa;
    }
    .class-item:last-child {
      border-bottom: none;
    }
    .class-info {
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .class-badge {
      background: linear-gradient(135deg, #177a03 0%, #145a02 100%);
      color: white;
      padding: 6px 14px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 13px;
    }
    .student-photos {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-top: 8px;
    }
    .student-photo {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #ddd;
    }
    .row-chart-container {
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
      margin: 15px;
    }
    .row-chart-container .chart-container {
      flex: 1;
      min-width: 280px;
      margin: 0;
    }
    .chart-canvas-wrapper {
      position: relative;
      width: 100%;
      height: 300px;
    }
    @media (max-width: 992px) {
      .row-chart-container .chart-container {
        min-width: 250px;
      }
      .chart-canvas-wrapper {
        height: 280px;
      }
    }
    @media (max-width: 768px) {
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
      }
      .class-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
      .row-chart-container {
        flex-direction: column;
        margin: 10px;
        gap: 15px;
      }
      .row-chart-container .chart-container {
        min-width: 100%;
        margin: 0;
      }
      .chart-container {
        padding: 20px;
        margin: 10px;
      }
      .chart-container h3 {
        font-size: 16px;
      }
      .students-by-class {
        padding: 20px;
        margin: 10px;
      }
      .chart-canvas-wrapper {
        height: 250px;
      }
    }
    @media (max-width: 480px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }
      .chart-container {
        padding: 15px;
        margin: 10px;
      }
      .chart-container h3 {
        font-size: 15px;
      }
      .students-by-class {
        padding: 15px;
        margin: 10px;
      }
      .class-item {
        padding: 12px;
      }
      .class-badge {
        font-size: 12px;
        padding: 5px 12px;
      }
      .chart-canvas-wrapper {
        height: 220px;
      }
      .row-chart-container {
        margin: 5px;
        gap: 10px;
      }
    }
  </style>
</head>
<body>
  <div class="container-fluid p-3">
    <div class="stats-grid">
      <div class="stat-card">
        <h5><i class="fas fa-user-graduate"></i> Total Students</h5>
        <h2><?= $total_students ?></h2>
        <div class="stat-icon"><i class="fas fa-users"></i></div>
      </div>

      <div class="stat-card">
        <h5><i class="fas fa-layer-group"></i> Total Batches</h5>
        <h2><?= $batch_count ?></h2>
        <div class="stat-icon"><i class="fas fa-sitemap"></i></div>
      </div>

      <div class="stat-card">
        <h5><i class="fas fa-school"></i> Total Classes</h5>
        <h2><?= $class_count ?></h2>
        <div class="stat-icon"><i class="fas fa-building"></i></div>
      </div>

      <div class="stat-card">
        <h5><i class="fas fa-calendar-plus"></i> Year Admission</h5>
        <h2><?= $year_admission_students ?></h2>
        <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
      </div>

      <div class="stat-card">
        <h5><i class="fas fa-chalkboard-teacher"></i> Total Teachers</h5>
        <h2><?= $teacher_count ?></h2>
        <div class="stat-icon"><i class="fas fa-graduation-cap"></i></div>
      </div>

      <div class="stat-card">
        <h5><i class="fas fa-users"></i> Total Staff</h5>
        <h2><?= $staff_count ?></h2>
        <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
      </div>

      <div class="stat-card">
        <h5><i class="fas fa-user-shield"></i> Total Admins</h5>
        <h2><?= $admin_count ?></h2>
        <div class="stat-icon"><i class="fas fa-user-cog"></i></div>
      </div>
    </div>

    <div class="row-chart-container">
      <?php if (!empty($chart_data)): ?>
      <div class="chart-container">
        <h3><i class="fas fa-chart-bar"></i> Students Distribution</h3>
        <div class="chart-canvas-wrapper">
          <canvas id="studentsChart"></canvas>
        </div>
      </div>
      <?php endif; ?>

      <?php if (array_sum($gpa_data) > 0): ?>
      <div class="chart-container">
        <h3><i class="fas fa-chart-pie"></i> GPA Distribution</h3>
        <div class="chart-canvas-wrapper">
          <canvas id="gpaChart"></canvas>
        </div>
      </div>
      <?php endif; ?>
    </div>
    
  </div>

  <script>
    <?php if (!empty($chart_data)): ?>
    const ctx = document.getElementById('studentsChart').getContext('2d');
    const chart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?= $chart_labels ?>,
        datasets: [{
          label: 'Students',
          data: <?= $chart_values ?>,
          backgroundColor: [
            'rgba(23, 122, 3, 0.8)',
            'rgba(23, 122, 3, 0.7)',
            'rgba(23, 122, 3, 0.6)',
            'rgba(23, 122, 3, 0.5)',
            'rgba(23, 122, 3, 0.4)',
            'rgba(23, 122, 3, 0.6)',
            'rgba(23, 122, 3, 0.7)',
            'rgba(23, 122, 3, 0.5)'
          ],
          borderColor: '#177a03',
          borderWidth: 1,
          borderRadius: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: '#177a03',
            padding: 10,
            titleFont: { size: 12 },
            bodyFont: { size: 12 }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1
            },
            grid: {
              color: 'rgba(0, 0, 0, 0.05)'
            }
          },
          x: {
            ticks: {
              maxRotation: 45,
              minRotation: 45,
              font: { size: 11 }
            },
            grid: {
              display: false
            }
          }
        }
      }
    });
    <?php endif; ?>

    <?php if (array_sum($gpa_data) > 0): ?>
    const gpaCtx = document.getElementById('gpaChart').getContext('2d');
    const gpaChart = new Chart(gpaCtx, {
      type: 'doughnut',
      data: {
        labels: <?= $gpa_labels ?>,
        datasets: [{
          data: <?= $gpa_values ?>,
          backgroundColor: [
            '#27ae60',
            '#2ecc71',
            '#82e0aa',
            '#f39c12',
            '#e67e22',
            '#e74c3c',
            '#c0392b'
          ],
          borderWidth: 2,
          borderColor: '#fff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            position: 'right',
            labels: {
              padding: 15,
              font: { size: 12 }
            }
          },
          tooltip: {
            backgroundColor: '#177a03',
            padding: 10,
            titleFont: { size: 12 },
            bodyFont: { size: 12 }
          }
        }
      }
    });
    <?php endif; ?>
  </script>
</body>
</html>

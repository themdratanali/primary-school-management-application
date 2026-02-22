<?php
include '../config/config.php';

$student_count = $conn->query("SELECT COUNT(*) as total FROM students")->fetch_assoc()['total'];
$class_count = $conn->query("SELECT COUNT(*) as total FROM classes")->fetch_assoc()['total'];
$batch_count = $conn->query("SELECT COUNT(*) as total FROM batches")->fetch_assoc()['total'];

$teacher_count = $conn->query("SELECT COUNT(*) as total FROM teachers")->fetch_assoc()['total'];
$total_fees = 0;

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
        $total_fees += (float)$sum;
      }
    }
  }
}

$class_student_counts = [];
$result = $conn->query("SELECT classes.name AS class_name, COUNT(students.id) AS student_count 
                        FROM classes 
                        LEFT JOIN students ON classes.id = students.class_id 
                        GROUP BY classes.id 
                        ORDER BY classes.name ASC");
while ($row = $result->fetch_assoc()) {
  $class_student_counts[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Dashboard</title>
  <link rel="shortcut icon" type="image/jpg" href="../assets/img/এ্যাপেক্স মডেল স্কুল.png"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="/apex/assets/fontawesome/fontawesome-free-6.4.0-web/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/dashboardhome.css">
</head>

<body>
  <div class="dashboard-row">
    <div class="card card-box bg-gradient-blue">
      <div class="card-body">
        <h5>Total Students</h5>
        <h2><?= $student_count ?></h2>
        <div class="icon-wrap"><i class="fas fa-user-graduate"></i></div>
      </div>
    </div>

    <div class="card card-box bg-gradient-pink">
      <div class="card-body">
        <h5>Total Batches</h5>
        <h2><?= $batch_count ?></h2>
        <div class="icon-wrap"><i class="fas fa-layer-group"></i></div>
      </div>
    </div>

    <div class="card card-box bg-gradient-purple">
      <div class="card-body">
        <h5>Total Teachers</h5>
        <h2><?= $teacher_count ?></h2>
        <div class="icon-wrap"><i class="fas fa-user-tie"></i></div>
      </div>
    </div>

    <div class="card card-box bg-gradient-orange">
      <div class="card-body">
        <h5>Total Fees Collected</h5>
        <h2>৳ <?= number_format($total_fees, 2) ?></h2>
        <div class="icon-wrap"><i class="fas fa-coins"></i></div>
      </div>
    </div>
  </div>
</body>
</html>

<?php
require_once __DIR__ . '/../../env/session.php';
include '../../env/config.php';

// Auth check
if (!isset($_SESSION['admin'])) {
  ams_redirect(ams_admin_url('login'));
  exit;
}

// Fetch all staff members
$sql = "SELECT * FROM staff ORDER BY name";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff List - Apex School</title>
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/uploads/images/এ্যাপেক্স মডেল স্কুল.png" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/dashboard_frame.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/teachers_list.css">
</head>

<body>
  <div class="content-wrapper">
    <div class="page-header d-flex justify-content-between align-items-center">
      <h4 class="mb-0" style="color: white; font-weight: 600; font-size: 20px;">All Staff List</h4>
      <a href="<?php echo ams_admin_url('add_staff'); ?>" class="btn btn-light btn-sm">
        <i class="fas fa-plus me-1"></i> Add New Staff
      </a>
    </div>
    
    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Photo</th>
                <th>Name</th>
                <th>Designation</th>
                <th>Phone</th>
                <th>Email</th>
              </tr>
            </thead>
<tbody>
<?php
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $photo = BASE_URL . '/uploads/staff/default-photo.jpg';
        if (!empty($row['photo'])) {
            $cleanPath = str_replace('../', '', $row['photo']);
            $fsPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);
            if (file_exists($fsPath)) {
                $photo = BASE_URL . '/' . $cleanPath;
            }
        }
        ?>
        <tr>
            <td><img src="<?= htmlspecialchars($photo) ?>" alt="Photo" class="photo-thumb" onerror="this.src='<?php echo BASE_URL; ?>/uploads/images/logo.png'"></td>
            <td><a href="staff_profile?id=<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></a></td>
            <td><span class="list-badge"><i class="fas fa-briefcase"></i><?= htmlspecialchars($row['designation']) ?></span></td>
            <td><?= htmlspecialchars($row['phone']) ?></td>
            <td><?= htmlspecialchars($row['email'] ?? 'N/A') ?></td>
        </tr>
    <?php
}
} else {
    ?>
    <tr>
        <td colspan="5" class="no-data">
            <i class="fas fa-folder-open fa-3x mb-3"></i>
            <p>No staff found.</p>
        </td>
    </tr>
<?php
}
?>
        </tbody>
        </table>
    </div>
</div>
</div>
</div>
</body>
</html>












<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';
if (!isset($_SESSION['admin'])) {
  header('Location: login.php');
  exit;
}

$sql = "SELECT * FROM staff ORDER BY name";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Staff List</title>
  <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f5f7fa;
    }

    .container {
      max-width: 1100px;
      margin: 0 auto;
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 1px 5px rgba(0, 0, 0, 0.08);
    }

    .dashboard-title {
      font-size: 20px;
      color: #333;
      margin-bottom: 15px;
      text-align: center;
      font-weight: 700;
    }

    .add-btn {
      display: inline-block;
      background: #177a03;
      color: white;
      padding: 10px 20px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
      margin-bottom: 15px;
      font-size: 13px;
      transition: all 0.2s;
    }

    .add-btn:hover {
      background: #145a02;
      color: white;
    }

    .export-btn {
      display: inline-block;
      background: #177a03;
      color: white;
      padding: 10px 20px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
      margin-left: 10px;
      margin-bottom: 15px;
      font-size: 13px;
      transition: all 0.2s;
    }

    .export-btn:hover {
      background: #145a02;
      color: white;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      border-radius: 8px;
      overflow: hidden;
      background: #fff;
    }

    th,
    td {
      padding: 12px 15px;
      text-align: left;
      color: #333;
      font-size: 13px;
      border-bottom: 1px solid #eee;
    }

    th {
      background: #177a03;
      color: white;
      font-weight: 600;
    }

    th:first-child, td:first-child {
      width: 60px;
      text-align: center;
    }

    tr:hover {
      background: #f9f9f9;
    }

    a {
      color: #177a03;
      text-decoration: none;
      font-weight: 600;
    }

    a:hover {
      color: #145a02;
    }

    .photo-thumb {
      width: 45px;
      height: 45px;
      object-fit: cover;
      border-radius: 50%;
      border: 2px solid #ddd;
    }

    .no-data {
      text-align: center;
      padding: 40px;
      color: #999;
    }

    @media (max-width: 600px) {
      th,
      td {
        padding: 10px 12px;
        font-size: 12px;
      }

      .photo-thumb {
        width: 40px;
        height: 40px;
      }
    }
  </style>

</head>

<body>
  <div class="container">
    <div class="dashboard-title">All Staff</div>
    <a href="export_staff_excel.php" class="export-btn">
      📥 Export to Excel (CSV)
    </a>
    <table>
      <thead>
        <tr>
          <th>Photo</th>
          <th>Name</th>
          <th>Designation</th>
          <th>Phone</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()):
            $photo_path = '';
            if (!empty($row['photo'])) {
                $possible_paths = [
                    '../' . $row['photo'],
                    $row['photo'],
                    '../uploads/staff/' . basename($row['photo']),
                    '../uploads/' . basename($row['photo'])
                ];
                foreach ($possible_paths as $p) {
                    if (file_exists($p)) {
                        $photo_path = $p;
                        break;
                    }
                }
            }
            if (empty($photo_path)) {
                $photo_path = '../assets/img/logo.png';
            }
          ?>
            <tr>
              <td><img src="<?= htmlspecialchars($photo_path) ?>" alt="Photo" class="photo-thumb" onerror="this.src='../assets/img/logo.png'"></td>
              <td><a href="staff_profile.php?id=<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></a></td>
              <td><?= htmlspecialchars($row['designation']) ?></td>
              <td><?= htmlspecialchars($row['phone']) ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
            <tr>
              <td colspan="4" class="no-data">No staff found.</td>
            </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</body>

</html>


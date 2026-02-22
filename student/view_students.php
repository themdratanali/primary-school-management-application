<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

// Allow both admin and teacher to view student list
if (!isset($_SESSION['admin']) && !isset($_SESSION['teacher_id'])) {
  header('Location: ../admin/login.php');
  exit;
}

$is_admin = isset($_SESSION['admin']);
$is_teacher = isset($_SESSION['teacher_id']);

$classes = $conn->query("SELECT * FROM classes ORDER BY name");
$batches = $conn->query("SELECT * FROM batches ORDER BY name");

$students = [];
$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $batch_id = intval($_POST['batch_id'] ?? 0);
  $class_id = intval($_POST['class_id'] ?? 0);

  if ($batch_id && $class_id) {
    $batch_res = $conn->query("SELECT name FROM batches WHERE id = $batch_id");
    $class_res = $conn->query("SELECT name FROM classes WHERE id = $class_id");

    if ($batch_res && $class_res && $batch_res->num_rows > 0 && $class_res->num_rows > 0) {
      $batch_name = preg_replace('/\s+/', '', $batch_res->fetch_assoc()['name']);
      $class_name = preg_replace('/\s+/', '', $class_res->fetch_assoc()['name']);
      $table_name = "Student_{$batch_name}_{$class_name}";

      $checkTable = $conn->query("SHOW TABLES LIKE '$table_name'");
      if ($checkTable->num_rows > 0) {
        $students_res = $conn->query("
            SELECT s.id, s.name, s.roll, s.photo, s.id as student_id
            FROM `$table_name` s
            ORDER BY s.roll ASC
        ");
        if ($students_res) {
          while ($row = $students_res->fetch_assoc()) {
            // Get user info for this student
            $user_res = $conn->query("SELECT id, email, plain_password FROM student_users WHERE student_id = " . $row['student_id']);
            $user_info = $user_res ? $user_res->fetch_assoc() : null;
            
            $students[] = [
              'id' => $row['id'],
              'name' => $row['name'],
              'roll' => $row['roll'],
              'photo' => $row['photo'],
              'table_name' => $table_name,
              'email' => $user_info['email'] ?? null,
              'plain_password' => $user_info['plain_password'] ?? null,
              'user_id' => $user_info['id'] ?? null
            ];
          }
          $message = "Showing students for Batch: <strong>$batch_name</strong>, Class: <strong>$class_name</strong>";
        } else {
          $errors[] = "Error fetching students: " . $conn->error;
        }
      } else {
        $errors[] = "No student table found for the selected batch and class.";
      }
    } else {
      $errors[] = "Invalid Batch or Class selection.";
    }
  } else {
    $errors[] = "Please select both Batch and Class.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>View Student Profiles - Apex School</title>
  <link rel="shortcut icon" href="../assets/img/logo.png" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/apex/assets/fontawesome/fontawesome-free-6.4.0-web/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/view_students.css">
  <style>
    .content-wrapper {
      background-color: #f8f9fa;
      min-height: 100vh;
      width: 100%;
    }
    .page-header {
      background: linear-gradient(135deg, #177a03 0%, #219a04 100%);
      color: white;
      padding: 20px 30px;
      border-radius: 10px;
      margin-bottom: 20px;
    }
    .btn-set {
      background-color: #177a03;
      color: white;
      padding: 5px 12px;
      border-radius: 5px;
      font-size: 12px;
      text-decoration: none;
      cursor: pointer;
      border: none;
    }
    .btn-set:hover {
      background-color: #145d02;
      color: white;
    }
    .password-display {
      font-family: monospace;
      font-size: 13px;
    }
    /* Modal Styles */
    .modal-header {
      background: linear-gradient(135deg, #177a03 0%, #219a04 100%);
      color: white;
    }
    .modal-header .btn-close {
      filter: brightness(0) invert(1);
    }
  </style>
</head>

<body>
  <div class="content-wrapper">
    <div class="page-header d-flex justify-content-between align-items-center">
      <h4 class="mb-0"><i class="fas fa-user-graduate me-2"></i>View Student Profiles</h4>
      <?php if ($is_admin): ?>
        <a href="add_student.php" class="btn btn-light btn-sm">
          <i class="fas fa-plus me-1"></i>Add New Student
        </a>
      <?php endif; ?>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endforeach; ?>
    <?php endif; ?>

    <div class="card mb-4">
      <div class="card-body">
        <form method="post" class="row g-3 align-items-end">
          <div class="col-auto">
            <label class="col-form-label">Batch:</label>
          </div>
          <div class="col-auto">
            <select name="batch_id" class="form-select" required style="width: auto;">
              <option value="">-- Select Batch --</option>
              <?php $batches->data_seek(0); while ($b = $batches->fetch_assoc()): ?>
                <option value="<?= $b['id'] ?>" <?= isset($batch_id) && $batch_id == $b['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($b['name']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-auto">
            <label class="col-form-label">Class:</label>
          </div>
          <div class="col-auto">
            <select name="class_id" class="form-select" required style="width: auto;">
              <option value="">-- Select Class --</option>
              <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>" <?= isset($class_id) && $class_id == $c['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['name']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-search me-1"></i>Show Students
            </button>
          </div>
        </form>
      </div>
    </div>

    <?php if (!empty($students)): ?>
      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Roll</th>
                  <th>Photo</th>
                  <th>Name</th>
                  <th>Email</th>
                  <?php if ($is_admin): ?>
                    <th>Password</th>
                  <?php endif; ?>
                  <th>Profile</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($students as $stu): ?>
                  <tr>
                    <td><?= htmlspecialchars($stu['roll']) ?></td>
                    <td>
                      <?php
                      $photoPath = (!empty($stu['photo']) && file_exists($stu['photo'])) ? $stu['photo'] : '../uploads/students/default-photo.jpg';
                      ?>
                      <img src="<?= htmlspecialchars($photoPath) ?>" alt="Photo" class="photo-thumb" />
                    </td>
                    <td><?= htmlspecialchars($stu['name']) ?></td>
                    <td><?= htmlspecialchars($stu['email'] ?? 'N/A') ?></td>
                    <?php if ($is_admin): ?>
                      <td>
                        <?php if (!empty($stu['user_id'])): ?>
                          <span class="password-display me-2"><?= htmlspecialchars($stu['plain_password'] ?? '') ?></span>
                          <button type="button" class="btn-set" onclick="openPasswordModal(<?= $stu['user_id'] ?>, '<?= htmlspecialchars($stu['plain_password'] ?? '') ?>')">
                            [Set]
                          </button>
                        <?php else: ?>
                          <span style="color:#999;">No login</span>
                        <?php endif; ?>
                      </td>
                    <?php endif; ?>
                    <td>
                      <a href="student_profile.php?table=<?= urlencode($stu['table_name']) ?>&id=<?= urlencode($stu['id']) ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye me-1"></i>View
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <?php if (!empty($batch_id) && !empty($class_id)): ?>
        <div class="mt-3 text-center">
          <a href="../teacher/export_students_excel.php?batch_id=<?= $batch_id ?>&class_id=<?= $class_id ?>"
             class="btn btn-success">
            <i class="fas fa-file-excel me-1"></i>Export to Excel (CSV)
          </a>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- Password Modal -->
  <div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-key me-2"></i>Set Student Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="studentUserId">
          <div class="mb-3">
            <label for="studentNewPassword" class="form-label">New Password</label>
            <input type="text" class="form-control" id="studentNewPassword" placeholder="Enter password">
          </div>
          <div id="studentPasswordMessage"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveStudentPassword()">
            <i class="fas fa-save me-1"></i>Save
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    var studentPasswordModal;

    $(document).ready(function() {
      studentPasswordModal = new bootstrap.Modal(document.getElementById('passwordModal'));
    });

    function openPasswordModal(userId, currentPassword) {
      $('#studentUserId').val(userId);
      $('#studentNewPassword').val(currentPassword);
      $('#studentPasswordMessage').html('');
      studentPasswordModal.show();
    }

    function saveStudentPassword() {
      var userId = $('#studentUserId').val();
      var newPassword = $('#studentNewPassword').val().trim();

      if (newPassword === '') {
        $('#studentPasswordMessage').html('<span class="text-danger">Password cannot be empty!</span>');
        return;
      }

      $('#studentPasswordMessage').html('<span class="text-info">Saving...</span>');

      $.post('../admin/update_student_password.php', {
        id: userId,
        password: newPassword
      }, function(response) {
        if (response.success) {
          $('#studentPasswordMessage').html('<span class="text-success">Password saved successfully!</span>');
          setTimeout(function() {
            studentPasswordModal.hide();
            location.reload();
          }, 1000);
        } else {
          $('#studentPasswordMessage').html('<span class="text-danger">' + (response.error || 'Error saving password') + '</span>');
        }
      }, 'json').fail(function() {
        $('#studentPasswordMessage').html('<span class="text-danger">Error saving password!</span>');
      });
    }
  </script>
</body>
</html>

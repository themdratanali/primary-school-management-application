<?php
require_once __DIR__ . '/../../env/session.php';
include '../../env/config.php';

// Auth check
if (!isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

$is_admin = true;

// Fetch classes and batches
$classes = $conn->query("SELECT * FROM classes ORDER BY name");
$batches = $conn->query("SELECT * FROM batches ORDER BY name");

// Initialize variables
$students = [];
$message = '';
$errors = [];

// Handle POST request for student search
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $batch_id = intval($_POST['batch_id'] ?? 0);
  $class_id = intval($_POST['class_id'] ?? 0);

  if ($batch_id && $class_id) {
    $batch_res = $conn->query("SELECT name FROM batches WHERE id = $batch_id");
    $class_res = $conn->query("SELECT name FROM classes WHERE id = $class_id");

    if ($batch_res && $class_res && $batch_res->num_rows > 0 && $class_res->num_rows > 0) {
      $batch_name_raw = $batch_res->fetch_assoc()['name'];
      $class_name_raw = $class_res->fetch_assoc()['name'];
      $batch_name = strtolower(str_replace(' ', '_', $batch_name_raw));
      $class_name = strtolower(str_replace(' ', '_', $class_name_raw));
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
  <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/uploads/images/এ্যাপেক্স মডেল স্কুল.png" type="image/x-icon">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/dashboard_frame.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/teachers_list.css">
</head>

<body>
  <div class="content-wrapper">
    <div class="page-header d-flex justify-content-between align-items-center">
      <h4 class="mb-0" style="color: white; font-weight: 600; font-size: 20px;">All Student Profiles</h4>
      <?php if ($is_admin): ?>
        <a href="add_student" class="btn btn-light btn-sm">
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
              <option value="">Select Batch</option>
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
              <option value="">Select Class</option>
              <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>" <?= isset($class_id) && $class_id == $c['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['name']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-primary">
              <i></i> Show Students
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
                      <td><span class="list-badge"><i class="fas fa-hashtag"></i><?= htmlspecialchars($stu['roll']) ?></span></td>
<td>
                      <?php
                      $photoPath = BASE_URL . '/uploads/students/default-photo.jpg';
                      if (!empty($stu['photo'])) {
                          $storedPath = $stu['photo'];
                          $cleanPath = str_replace('../', '', $storedPath);
                          $fsPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);
                          if (file_exists($fsPath)) {
                              $photoPath = BASE_URL . '/' . $cleanPath;
                          }
                      }
                      ?>
                      <img src="<?= htmlspecialchars($photoPath) ?>" alt="Photo" class="photo-thumb" onerror="this.src='<?php echo BASE_URL; ?>/uploads/students/default-photo.jpg'" />
                    </td>
                    <td><?= htmlspecialchars($stu['name']) ?></td>
                    <td><span class="list-badge"><i class="fas fa-envelope"></i><?= htmlspecialchars($stu['email'] ?? 'N/A') ?></span></td>
                    <?php if ($is_admin): ?>
                      <td>
                        <?php if (!empty($stu['user_id'])): ?>
                          <button type="button" class="btn-set" onclick="openPasswordModal(<?= $stu['user_id'] ?>, '<?= htmlspecialchars($stu['plain_password'] ?? '') ?>')">
                            <i class="fas fa-key"></i> Set
                          </button>
                        <?php else: ?>
                          <span style="color:#999;">No login</span>
                        <?php endif; ?>
                      </td>
                    <?php endif; ?>
                    <td>
                      <a href="<?= ams_admin_url('student_profile') ?>?table=<?= urlencode($stu['table_name']) ?>&id=<?= urlencode($stu['id']) ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye me-1"></i> View
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>


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
            <i class="fas fa-save me-1"></i> Save
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

      $.post('<?php echo ams_admin_url('update_student_password'); ?>', {
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












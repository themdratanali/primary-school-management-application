<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$sql = "SELECT t.*, GROUP_CONCAT(DISTINCT s.name SEPARATOR ', ') AS subjects,
        GROUP_CONCAT(DISTINCT s.id) AS subject_ids
        FROM teachers t
        LEFT JOIN teacher_subjects ts ON t.id = ts.teacher_id
        LEFT JOIN subjects s ON ts.subject_id = s.id
        GROUP BY t.id
        ORDER BY t.name";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teachers List - Apex School</title>
    <link rel="shortcut icon" href="../assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/apex/assets/fontawesome/fontawesome-free-6.4.0-web/css/all.min.css">
    <style>
        .content-wrapper {
            padding: 20px;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .page-header {
            background: linear-gradient(135deg, #177a03 0%, #219a04 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
        }
        .photo-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #177a03;
        }
        .table th {
            background-color: #177a03;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        .table td {
            vertical-align: middle;
            font-size: 14px;
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
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-size: 16px;
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
        .form-control:focus {
            border-color: #177a03;
            box-shadow: 0 0 0 0.2rem rgba(23, 122, 3, 0.25);
        }
        .btn-primary {
            background-color: #177a03;
            border-color: #177a03;
        }
        .btn-primary:hover {
            background-color: #145d02;
            border-color: #145d02;
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i> Teachers List</h4>
            <a href="add_teacher.php" class="btn btn-light btn-sm">
                <i class="fas fa-plus me-1"></i> Add New Teacher
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
                                <th>Present Address</th>
                                <th>Phone</th>
                                <th>Subject(s)</th>
                                <th>Email</th>
                                <th>Password</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()):
                                    $photo = (!empty($row['photo']) && file_exists($row['photo'])) ? $row['photo'] : '../uploads/teachers/default-photo.jpg';
                                    $currentPassword = $row['plain_password'] ?? '';
                                ?>
                                    <tr>
                                        <td><img src="<?= htmlspecialchars($photo) ?>" alt="Photo" class="photo-thumb"></td>
                                        <td><a href="teacher_profile.php?id=<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></a></td>
                                        <td><?= htmlspecialchars($row['designation'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['present_address'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['phone']) ?></td>
                                        <td><?= htmlspecialchars($row['subjects'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['email'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="password-display me-2"><?= htmlspecialchars($currentPassword) ?></span>
                                            <button type="button" class="btn-set" onclick="openPasswordModal(<?= $row['id'] ?>, '<?= htmlspecialchars($currentPassword) ?>')">
                                                [Set]
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="no-data">
                                        <i class="fas fa-folder-open fa-3x mb-3"></i>
                                        <p>No teachers found.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Password Modal -->
    <div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Set Teacher Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="teacherId">
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">New Password</label>
                        <input type="text" class="form-control" id="newPassword" placeholder="Enter password">
                    </div>
                    <div id="passwordMessage"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="savePassword()">
                        <i class="fas fa-save me-1"></i>Save
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var passwordModal;

        $(document).ready(function() {
            passwordModal = new bootstrap.Modal(document.getElementById('passwordModal'));
        });

        function openPasswordModal(teacherId, currentPassword) {
            $('#teacherId').val(teacherId);
            $('#newPassword').val(currentPassword);
            $('#passwordMessage').html('');
            passwordModal.show();
        }

        function savePassword() {
            var teacherId = $('#teacherId').val();
            var newPassword = $('#newPassword').val().trim();

            if (newPassword === '') {
                $('#passwordMessage').html('<span class="text-danger">Password cannot be empty!</span>');
                return;
            }

            $('#passwordMessage').html('<span class="text-info">Saving...</span>');

            $.post('../admin/update_teacher_password.php', {
                teacher_id: teacherId,
                password: newPassword
            }, function(response) {
                if (response.success) {
                    $('#passwordMessage').html('<span class="text-success">Password saved successfully!</span>');
                    setTimeout(function() {
                        passwordModal.hide();
                        location.reload();
                    }, 1000);
                } else {
                    $('#passwordMessage').html('<span class="text-danger">' + (response.error || 'Error saving password') + '</span>');
                }
            }, 'json').fail(function() {
                $('#passwordMessage').html('<span class="text-danger">Error saving password!</span>');
            });
        }
    </script>
</body>
</html>

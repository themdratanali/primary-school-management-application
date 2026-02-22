<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';
include '../includes/csrf.php';

if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['teacher_id'];

// Get teacher info
$stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

// Handle form submission for add/edit homework
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $csrf_token = $_POST['_token'] ?? '';
        if (!ams_csrf_is_valid($csrf_token)) {
            $error = 'Invalid CSRF token';
        } else {
            $batch_id = intval($_POST['batch_id']);
            $class_id = intval($_POST['class_id']);
            $title = trim($_POST['title']);
            $details = trim($_POST['details']);
            
            if ($batch_id > 0 && $class_id > 0 && !empty($title)) {
                $target_dir = "../uploads/homework/";
                
                // Create directory if not exists
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $file_name = null;
                $file_path = null;
                $file_type = null;
                
                // Handle file upload
                if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
                    $file = $_FILES['file'];
                    $original_name = basename($file['name']);
                    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                    
                    // Allowed extensions
                    $allowed_ext = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($ext, $allowed_ext)) {
                        $file_name = date('Ymdhis_') . $original_name;
                        $file_path = $target_dir . $file_name;
                        $file_type = $ext;
                        
                        move_uploaded_file($file['tmp_name'], $file_path);
                    }
                }
                
                if ($_POST['action'] == 'add') {
                    $stmt = $conn->prepare("INSERT INTO homeworks (batch_id, class_id, title, details, file_name, file_path, file_type, teacher_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                    $stmt->bind_param("iisssssi", $batch_id, $class_id, $title, $details, $file_name, $file_path, $file_type, $teacher_id);
                    
                    if ($stmt->execute()) {
                        $success = 'Homework added successfully!';
                    } else {
                        $error = 'Failed to add homework.';
                    }
                    $stmt->close();
                } elseif ($_POST['action'] == 'edit' && isset($_POST['homework_id'])) {
                    $homework_id = intval($_POST['homework_id']);
                    
                    if ($file_name) {
                        // Get old file to delete
                        $old_stmt = $conn->prepare("SELECT file_path FROM homeworks WHERE id = ? AND teacher_id = ?");
                        $old_stmt->bind_param("ii", $homework_id, $teacher_id);
                        $old_stmt->execute();
                        $old_result = $old_stmt->get_result();
                        if ($old_row = $old_result->fetch_assoc()) {
                            if (file_exists($old_row['file_path'])) {
                                unlink($old_row['file_path']);
                            }
                        }
                        $old_stmt->close();
                        
                        $stmt = $conn->prepare("UPDATE homeworks SET batch_id = ?, class_id = ?, title = ?, details = ?, file_name = ?, file_path = ?, file_type = ? WHERE id = ? AND teacher_id = ?");
                        $stmt->bind_param("iisssssii", $batch_id, $class_id, $title, $details, $file_name, $file_path, $file_type, $homework_id, $teacher_id);
                    } else {
                        $stmt = $conn->prepare("UPDATE homeworks SET batch_id = ?, class_id = ?, title = ?, details = ? WHERE id = ? AND teacher_id = ?");
                        $stmt->bind_param("iissii", $batch_id, $class_id, $title, $details, $homework_id, $teacher_id);
                    }
                    
                    if ($stmt->execute()) {
                        $success = 'Homework updated successfully!';
                    } else {
                        $error = 'Failed to update homework.';
                    }
                    $stmt->close();
                }
            } else {
                $error = 'Please fill all required fields.';
            }
        }
    }
}

// Handle delete homework
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("SELECT file_path FROM homeworks WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $delete_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (file_exists($row['file_path'])) {
            unlink($row['file_path']);
        }
        $conn->query("DELETE FROM homeworks WHERE id = $delete_id");
        $success = 'Homework deleted successfully!';
    }
    $stmt->close();
}

// Handle mark as done
if (isset($_GET['done'])) {
    $done_id = intval($_GET['done']);
    $stmt = $conn->prepare("UPDATE homeworks SET status = 'done' WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $done_id, $teacher_id);
    $stmt->execute();
    $stmt->close();
    $success = 'Homework marked as done!';
}

// Handle mark as active
if (isset($_GET['active'])) {
    $active_id = intval($_GET['active']);
    $stmt = $conn->prepare("UPDATE homeworks SET status = 'active' WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $active_id, $teacher_id);
    $stmt->execute();
    $stmt->close();
    $success = 'Homework marked as active!';
}

// Get batches
$batches = $conn->query("SELECT * FROM batches ORDER BY name DESC");

// Get classes
$classes = $conn->query("SELECT * FROM classes ORDER BY name");

// Get teacher's homework
$homeworks = $conn->query("SELECT h.*, b.name as batch_name, c.name as class_name, t.name as teacher_name 
    FROM homeworks h 
    LEFT JOIN batches b ON h.batch_id = b.id 
    LEFT JOIN classes c ON h.class_id = c.id 
    LEFT JOIN teachers t ON h.teacher_id = t.id 
    WHERE h.teacher_id = $teacher_id 
    ORDER BY h.created_at DESC");

// Get single homework for edit
$edit_homework = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM homeworks WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $edit_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_homework = $result->fetch_assoc();
    $stmt->close();
}

$csrf_token = ams_csrf_token();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Homework - Apex Model School</title>
    <link rel="shortcut icon" type="image/jpg" href="../assets/img/এ্যাপেক্স মডেল স্কুল.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/apex/assets/fontawesome/fontawesome-free-6.4.0-web/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <link rel="stylesheet" href="../assets/css/teacher_homework.css">
</head>

<body>
    <div class="container">
        <div class="page-header">
            <h2 class="page-title"><i class="fas fa-book"></i> Manage Homework</h2>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="results-layout">
            <div class="results-section">
                <h3><i class="fas fa-plus-circle"></i> <?php echo $edit_homework ? 'Edit Homework' : 'Add New Homework'; ?></h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="action" value="<?php echo $edit_homework ? 'edit' : 'add'; ?>">
                            <?php if ($edit_homework): ?>
                                <input type="hidden" name="homework_id" value="<?php echo $edit_homework['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label>Select Batch <span class="text-danger">*</span></label>
                                <select name="batch_id" id="batch_id" required>
                                    <option value="">Select Batch</option>
                                    <?php 
                                    $batches->data_seek(0);
                                    while ($batch = $batches->fetch_assoc()): ?>
                                        <option value="<?php echo $batch['id']; ?>" <?php echo ($edit_homework && $edit_homework['batch_id'] == $batch['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($batch['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Select Class <span class="text-danger">*</span></label>
                                <select name="class_id" id="class_id" required>
                                    <option value="">Select Class</option>
                                    <?php 
                                    $classes->data_seek(0);
                                    while ($class = $classes->fetch_assoc()): ?>
                                        <option value="<?php echo $class['id']; ?>" <?php echo ($edit_homework && $edit_homework['class_id'] == $class['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Homework Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" placeholder="Enter homework title" value="<?php echo $edit_homework ? htmlspecialchars($edit_homework['title']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Details</label>
                                <textarea name="details" rows="4" placeholder="Enter homework details"><?php echo $edit_homework ? htmlspecialchars($edit_homework['details']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Attach File (PDF, Image, Word)</label>
                                <input type="file" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
                                <?php if ($edit_homework && $edit_homework['file_name']): ?>
                                    <small class="text-muted">Current file: <?php echo htmlspecialchars($edit_homework['file_name']); ?></small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="btn-group">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i> <?php echo $edit_homework ? 'Update Homework' : 'Add Homework'; ?>
                                </button>
                                <?php if ($edit_homework): ?>
                                    <a href="homework.php" class="btn-secondary">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
            </div>
            
            <div class="results-section">
                <h3><i class="fas fa-list"></i> All Homework</h3>
                
                <div class="filter-tabs">
                    <a href="?filter=all" class="filter-tab <?php echo (!isset($_GET['filter']) || $_GET['filter'] == 'all') ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i> All
                    </a>
                    <a href="?filter=active" class="filter-tab <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'active') ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i> Active
                    </a>
                    <a href="?filter=done" class="filter-tab <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'done') ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i> Done
                    </a>
                </div>
                        <?php
                        $filter = $_GET['filter'] ?? 'all';
                        $where_clause = "WHERE h.teacher_id = $teacher_id";
                        if ($filter == 'active') {
                            $where_clause .= " AND h.status = 'active'";
                        } elseif ($filter == 'done') {
                            $where_clause .= " AND h.status = 'done'";
                        }
                        
                        $homework_list = $conn->query("SELECT h.*, b.name as batch_name, c.name as class_name 
                            FROM homeworks h 
                            LEFT JOIN batches b ON h.batch_id = b.id 
                            LEFT JOIN classes c ON h.class_id = c.id 
                            $where_clause 
                            ORDER BY h.created_at DESC");
                        ?>
                        
                        <?php if ($homework_list && $homework_list->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Class/Batch</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($hw = $homework_list->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($hw['title']); ?></strong>
                                                    <?php if ($hw['file_name']): ?>
                                                        <div class="file-info"><i class="fas fa-paperclip"></i> <?php echo htmlspecialchars($hw['file_name']); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="class-badge"><?php echo htmlspecialchars($hw['class_name']); ?></span>
                                                    <span class="batch-info"><?php echo htmlspecialchars($hw['batch_name']); ?></span>
                                                </td>
                                                <td><?php echo date('d M Y', strtotime($hw['created_at'])); ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo $hw['status'] == 'active' ? 'status-active' : 'status-done'; ?>">
                                                        <?php echo ucfirst($hw['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-btns">
                                                        <?php if ($hw['file_path'] && file_exists($hw['file_path'])): ?>
                                                            <a href="../download_file.php?file=<?php echo urlencode($hw['file_path']); ?>" class="action-btn btn-info" title="Download">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <a href="homework.php?edit=<?php echo $hw['id']; ?>" class="action-btn btn-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        
                                                        <?php if ($hw['status'] == 'active'): ?>
                                                            <a href="homework.php?done=<?php echo $hw['id']; ?>" class="action-btn btn-success" title="Mark as Done" onclick="return confirm('Mark this homework as done?');">
                                                                <i class="fas fa-check"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="homework.php?active=<?php echo $hw['id']; ?>" class="action-btn btn-secondary-action" title="Mark as Active">
                                                                <i class="fas fa-undo"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <a href="homework.php?delete=<?php echo $hw['id']; ?>" class="action-btn btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this homework?');">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-folder-open"></i>
                                <p class="text-muted">No homework found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
require_once __DIR__ . '/../../env/session.php';
include '../../env/config.php';

if (!isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $title = $_POST['title'] ?? '';
    $target_dir = ams_upload_dir('exam_results');
    
    // Create directory if not exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file = $_FILES['file'];
    $filename = date('Ymdhis_') . basename($file['name']);
    $file_path = $target_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        $stmt = $conn->prepare("INSERT INTO exam_results_files (title, filename, file_path, uploaded_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $title, $filename, $file_path, $_SESSION['admin']);
        $stmt->execute();
        $success = "File uploaded successfully!";
    } else {
        $error = "Failed to upload file!";
    }
}

// Handle file delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $query = "SELECT file_path FROM exam_results_files WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (file_exists($row['file_path'])) {
            unlink($row['file_path']);
        }
        $conn->query("DELETE FROM exam_results_files WHERE id = $id");
        $success = "File deleted successfully!";
    }
}

// Fetch all files
$files = $conn->query("SELECT * FROM exam_results_files ORDER BY uploaded_date DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Exam Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/manage_exam_results.css">
</head>
<body>
    <div class="dashboard-title">Manage Exam Results</div>
    <div class="container">
        <div class="left">
            <h2>Upload New Exam Result</h2>
            <?php if (isset($success)): ?>
                <div class="message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="message" style="background:#f8d7da; color:#721c24;"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="text" name="title" placeholder="e.g., Class 6 Annual Exam Result 2025" required>
                <input type="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png,.jpeg" required>
                <small style="color:#666; font-size:12px;">Allowed: PDF, DOC, XLS, JPG, PNG</small>
                <button type="submit"><i class="fas fa-upload"></i> Upload Exam Result</button>
            </form>
        </div>

        <div class="right">
            <h2>Uploaded Files</h2>
            <?php if ($files && $files->num_rows > 0): ?>
                <?php while ($file = $files->fetch_assoc()): ?>
                    <div class="file-item">
                        <div>
                            <strong><?= htmlspecialchars($file['title']) ?></strong><br>
                            <small><?= $file['filename'] ?> - <?= date('d M Y', strtotime($file['uploaded_date'])) ?></small>
                        </div>
                        <div>
                            <a href="<?= $file['file_path'] ?>" class="download-btn" download><i class="fas fa-download"></i></a>
                            <a href="?delete=<?= $file['id'] ?>" class="delete-btn" onclick="return confirm('Delete this file?')"><i class="fas fa-trash"></i></a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:#999; font-size:13px; text-align:center;">No files uploaded yet</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>











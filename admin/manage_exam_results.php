<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $title = $_POST['title'] ?? '';
    $target_dir = "../uploads/exam_results/";
    
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
    <link rel="stylesheet" href="/apex/assets/fontawesome/fontawesome-free-6.4.0-web/css/all.min.css">
    <style>
        body {
            background-color: #f5f7fa;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 5px rgba(0,0,0,0.08);
        }
        .page-title {
            color: #333;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .page-title i {
            color: #177a03;
            margin-right: 8px;
        }
        .upload-section {
            background: #f0f7eb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
        }
        .upload-section h4 {
            color: #333;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }
        .form-control {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 13px;
        }
        .form-control:focus {
            border-color: #177a03;
            box-shadow: 0 0 0 2px rgba(23, 122, 3, 0.1);
        }
        .btn-primary {
            background: #177a03;
            border-color: #177a03;
            border-radius: 6px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: #145a02;
            border-color: #145a02;
        }
        .file-list-title {
            color: #333;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .file-item {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #eee;
        }
        .file-item strong {
            color: #333;
            font-size: 14px;
        }
        .file-item small {
            color: #666;
            font-size: 12px;
        }
        .btn-info {
            background: #177a03;
            border-color: #177a03;
            color: white;
            border-radius: 4px;
            padding: 6px 10px;
            font-size: 12px;
        }
        .btn-info:hover {
            background: #145a02;
            border-color: #145a02;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            border-color: #dc3545;
            color: white;
            border-radius: 4px;
            padding: 6px 10px;
            font-size: 12px;
        }
        .btn-danger:hover {
            background: #c82333;
            border-color: #c82333;
            color: white;
        }
        .alert {
            border-radius: 6px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-title"><i class="fas fa-chart-bar"></i> Manage Exam Results</div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="upload-section">
            <h4>Upload New File</h4>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" placeholder="e.g., Class 6 Annual Exam Result 2025" required>
                </div>
                <div class="mb-3">
                    <label for="file" class="form-label">Select File</label>
                    <input type="file" class="form-control" id="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png,.jpeg" required>
                    <small class="text-muted">Allowed: PDF, DOC, XLS, JPG, PNG</small>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload</button>
            </form>
        </div>
        
        <div class="file-list-title">Uploaded Files</div>
        <?php if ($files && $files->num_rows > 0): ?>
            <?php while ($file = $files->fetch_assoc()): ?>
                <div class="file-item">
                    <div>
                        <strong><?= htmlspecialchars($file['title']) ?></strong><br>
                        <small><?= $file['filename'] ?> - <?= date('d M Y', strtotime($file['uploaded_date'])) ?></small>
                    </div>
                    <div>
                        <a href="<?= $file['file_path'] ?>" class="btn btn-sm btn-info" download><i class="fas fa-download"></i></a>
                        <a href="?delete=<?= $file['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this file?')"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-muted" style="color: #999; font-size: 13px;">No files uploaded yet</p>
        <?php endif; ?>
    </div>
</body>
</html>

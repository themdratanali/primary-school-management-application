<?php
$page_title = 'পরীক্ষার ফলাফল - Apex Model School';
include 'header.php';
?>
<style>
    .page-content {
        background-color: #f5f5f5;
        padding: 40px 0;
        width: 85%;
        margin: auto;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    .page-title { color: #333; font-weight: 700; font-size: 2rem; margin-bottom: 10px; text-align: center; }
    .page-subtitle { color: #666; text-align: center; margin-bottom: 30px; font-size: 1.1rem; }
    .table-container {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 40px;
    }
    .table {
        margin: 0;
    }
    .table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .table thead th {
        border: none;
        padding: 15px;
        font-weight: 600;
        text-align: center;
    }
    .table tbody td {
        padding: 12px 15px;
        border-bottom: 1px solid #e0e0e0;
        text-align: center;
        vertical-align: middle;
    }
    .table tbody tr:hover {
        background-color: #f9f9f9;
    }
    .no-data {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }
    .no-data i {
        font-size: 48px;
        display: block;
        margin-bottom: 15px;
        color: #ddd;
    }
</style>

<div class="page-content">
    <div class="container" style=" max-width: 1050px;">
        <h1 class="page-title">পরীক্ষার ফলাফল</h1>
        <p class="page-subtitle">সকল পরীক্ষার ফলাফল ও ঘোষণা</p>

        <div class="table-container">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th style="width: 15%;">ফাইল</th>
                        <th style="width: 35%;">নাম</th>
                        <th style="width: 20%;">তারিখ</th>
                        <th style="width: 30%;">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $result = $conn->query("SELECT * FROM exam_results_files ORDER BY uploaded_date DESC");
                
                if ($result && $result->num_rows > 0) {
                    while ($file = $result->fetch_assoc()) {
                        $ext = strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION));
                        $icon = ($ext == 'pdf') ? 'fa-file-pdf' : (in_array($ext, ['jpg', 'jpeg', 'png']) ? 'fa-image' : 'fa-file');
                        ?>
                        <tr>
                            <td><i class="fas <?= $icon ?>" style="font-size: 1.5rem; color: #dc3545;"></i></td>
                            <td><strong><?= htmlspecialchars($file['title']) ?></strong></td>
                            <td><?= date('d M Y', strtotime($file['uploaded_date'])) ?></td>
                            <td>
                                <a href="download_file.php?id=<?= $file['id'] ?>&type=exam_results" class="btn-download">
                                    <i class="fas fa-download"></i> ডাউনলোড
                                </a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="4" class="no-data">
                            <i class="fas fa-inbox"></i>
                            <p>পরীক্ষার ফলাফল উপলব্ধ নেই</p>
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

<?php include 'footer.php'; ?>

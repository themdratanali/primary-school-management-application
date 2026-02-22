<?php
$page_title = 'শিক্ষকদের তথ্য - Apex Model School';
include 'header.php';

$sql = "SELECT t.id, t.name, t.present_address, t.phone, t.email, t.photo, 
               GROUP_CONCAT(s.name SEPARATOR ', ') AS subjects
        FROM teachers t
        LEFT JOIN teacher_subjects ts ON t.id = ts.teacher_id
        LEFT JOIN subjects s ON ts.subject_id = s.id
        GROUP BY t.id
        ORDER BY t.name";
$result = $conn->query($sql);
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
    .page-title {
        color: #333;
        font-weight: 700;
        font-size: 2rem;
        margin-bottom: 10px;
        text-align: center;
    }
    .page-subtitle {
        color: #666;
        text-align: center;
        margin-bottom: 30px;
        font-size: 1.1rem;
    }
    .teachers-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 60px;
        margin-bottom: 40px;
    }
    @media (max-width: 1200px) {
        .teachers-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 768px) {
        .teachers-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 480px) {
        .teachers-grid {
            grid-template-columns: 1fr;
        }
    }
    .teacher-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .teacher-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    }
    .teacher-photo {
        width: 100%;
        height: 200px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .teacher-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .teacher-photo-default {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .teacher-photo-default i {
        font-size: 80px;
        color: white;
        opacity: 0.7;
    }
    .teacher-info {
        padding: 20px;
    }
    .teacher-name {
        font-size: 1.2rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 8px;
    }
    .teacher-detail {
        display: flex;
        align-items: flex-start;
        margin-bottom: 8px;
        color: #666;
        font-size: 0.9rem;
    }
    .teacher-detail i {
        color: #667eea;
        width: 20px;
        margin-right: 8px;
        margin-top: 3px;
    }
    .teacher-subjects {
        font-size: 0.85rem;
        color: #667eea;
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid #e0e0e0;
    }
    .no-data {
        text-align: center;
        padding: 60px 20px;
        color: #999;
        grid-column: 1 / -1;
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
        <h1 class="page-title">শিক্ষকদের তথ্য</h1>
        <p class="page-subtitle">আমাদের স্কুলের মেধাবী শিক্ষকবৃন্দ</p>

        <div class="teachers-grid">
            <?php
            if ($result && $result->num_rows > 0) {
                while ($teacher = $result->fetch_assoc()) {
                    $photoFilename = $teacher['photo'] ?? '';
                    $photoSrc = '';
                    if (!empty($photoFilename)) {
                        $photoFilename = str_replace('../', '', $photoFilename);
                        $photoSrc = $photoFilename;
                    }
                    ?>
                    <div class="teacher-card">
                        <div class="teacher-photo">
                            <?php if (!empty($photoSrc)) { ?>
                                <img src="<?php echo $photoSrc; ?>" alt="<?php echo htmlspecialchars($teacher['name']); ?>">
                            <?php } else { ?>
                                <div class="teacher-photo-default">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="teacher-info">
                            <div class="teacher-name"><?php echo htmlspecialchars($teacher['name']); ?></div>
                            <div class="teacher-detail">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($teacher['phone'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="teacher-detail">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($teacher['email'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="teacher-detail">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($teacher['present_address'] ?? 'N/A'); ?></span>
                            </div>
                            <?php if (!empty($teacher['subjects'])) { ?>
                                <div class="teacher-subjects">
                                    <i class="fas fa-book"></i> <?php echo htmlspecialchars($teacher['subjects']); ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="no-data">
                    <i class="fas fa-users"></i>
                    <p>কোনো শিক্ষকের তথ্য পাওয়া যায়নি</p>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

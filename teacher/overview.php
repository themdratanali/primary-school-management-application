<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['teacher_id'];

// Get teacher info
$teacher = $conn->query("SELECT * FROM teachers WHERE id = $teacher_id")->fetch_assoc();

// Get subjects assigned to this teacher
$subjects = $conn->query("
    SELECT s.name, s.code, c.name as class_name 
    FROM teacher_subjects ts 
    JOIN subjects s ON ts.subject_id = s.id 
    JOIN classes c ON s.class_id = c.id 
    WHERE ts.teacher_id = $teacher_id
");

// Get counts for dashboard
$total_students = 0;
$batch_count = $conn->query("SELECT COUNT(*) as total FROM batches")->fetch_assoc()['total'];
$class_count = $conn->query("SELECT COUNT(*) as total FROM classes")->fetch_assoc()['total'];
$teacher_count = $conn->query("SELECT COUNT(*) as total FROM teachers")->fetch_assoc()['total'];

// Count Total Staff from staff table
$staff_count = $conn->query("SELECT COUNT(*) as total FROM staff")->fetch_assoc()['total'];

// Get total students
$batches_all = $conn->query("SELECT * FROM batches ORDER BY name");
$classes_all = $conn->query("SELECT * FROM classes ORDER BY name");

while ($batch = $batches_all->fetch_assoc()) {
    $classes_all->data_seek(0);
    while ($class = $classes_all->fetch_assoc()) {
        $batch_name_clean = preg_replace('/\s+/', '', $batch['name']);
        $class_name_clean = preg_replace('/\s+/', '', $class['name']);
        $table_name = "Student_{$batch_name_clean}_{$class_name_clean}";
        
        $check_table = $conn->query("SHOW TABLES LIKE '$table_name'");
        if ($check_table && $check_table->num_rows > 0) {
            $count = $conn->query("SELECT COUNT(*) as total FROM `$table_name`")->fetch_assoc()['total'];
            $total_students += $count;
        }
    }
}

// Resolve photo URL for display
$photoUrl = '../assets/img/default-avatar.jpg';
if (!empty($teacher['photo'])) {
    $candidates = [
        $teacher['photo'],
        '../' . ltrim($teacher['photo'], '/'),
        '../uploads/teachers/' . basename($teacher['photo']),
    ];
    foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
            $photoUrl = $candidate;
            break;
        }
    }
}

// Parse education data
$teacherEducation = json_decode($teacher['education'] ?? '[]', true);
?>

<style>
    :root {
        --primary-color: #177a03;
        --primary-dark: #145a02;
        --secondary-color: #2c3e50;
        --text-color: #333333;
        --text-muted: #6c757d;
        --bg-light: #f5f7fa;
        --card-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
        --border-radius: 12px;
    }
    
    body {
        background: var(--bg-light);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 10px;
    }
    
    .profile-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, #1a9b06 100%);
        border-radius: var(--border-radius);
        padding: 30px;
        color: white;
        margin-bottom: 25px;
        position: relative;
        overflow: hidden;
    }
    
    .profile-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }
    
    .profile-header::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: -5%;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
    }
    
    .profile-header-content {
        display: flex;
        align-items: center;
        gap: 25px;
        position: relative;
        z-index: 1;
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 4px solid rgba(255, 255, 255, 0.3);
        object-fit: cover;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }
    
    .profile-info h2 {
        margin: 0 0 5px 0;
        font-size: 28px;
        font-weight: 700;
        color: white;
    }
    
    .profile-info .designation {
        font-size: 16px;
        opacity: 0.95;
        margin-bottom: 8px;
    }
    
    .profile-info .email {
        font-size: 14px;
        opacity: 0.85;
        display: flex;
        align-items: center;
    }
    
    .edit-profile-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.5);
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        z-index: 2;
    }
    
    .edit-profile-btn:hover {
        background: white;
        color: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 15px;
        margin-bottom: 25px;
    }
    
    .stat-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 20px;
        box-shadow: var(--card-shadow);
        transition: transform 0.2s, box-shadow 0.2s;
        border-left: 4px solid var(--primary-color);
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
    }
    
    .stat-card h5 {
        font-size: 13px;
        color: var(--text-muted);
        margin-bottom: 8px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .stat-card h2 {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
        color: var(--secondary-color);
    }
    
    .stat-icon {
        float: right;
        font-size: 28px;
        color: var(--primary-color);
        opacity: 0.2;
    }
    
    .content-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .info-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 25px;
        box-shadow: var(--card-shadow);
    }
    
    .info-card h3 {
        margin: 0 0 20px 0;
        font-size: 18px;
        color: var(--secondary-color);
        padding-bottom: 12px;
        border-bottom: 2px solid var(--bg-light);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .info-card h3 i {
        color: var(--primary-color);
    }
    
    .info-row {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 600;
        color: var(--text-muted);
        width: 140px;
        flex-shrink: 0;
        font-size: 14px;
    }
    
    .info-value {
        color: var(--text-color);
        font-size: 14px;
        flex: 1;
    }
    
    .info-value.empty {
        color: #ccc;
        font-style: italic;
    }
    
    .subject-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.2s;
        border: 1px solid #e9ecef;
    }
    
    .subject-card:hover {
        background: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }
    
    .subject-card strong {
        color: var(--secondary-color);
    }
    
    .subject-card .code {
        color: var(--text-muted);
        font-size: 12px;
    }
    
    .badge-class {
        background: var(--primary-color);
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .education-item {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 12px;
        border-left: 3px solid var(--primary-color);
    }
    
    .education-item h5 {
        margin: 0 0 5px 0;
        color: var(--secondary-color);
        font-size: 15px;
    }
    
    .education-item p {
        margin: 0;
        font-size: 13px;
        color: var(--text-muted);
    }
    
    .no-data {
        text-align: center;
        padding: 40px;
        color: var(--text-muted);
    }
    
    .no-data i {
        font-size: 40px;
        color: #ddd;
        margin-bottom: 15px;
        display: block;
    }
    
    @media (max-width: 768px) {
        .profile-header-content {
            flex-direction: column;
            text-align: center;
        }
        
        .edit-profile-btn {
            position: static;
            margin: 0px auto 45px;
            display: inline-flex;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
        }
        
        .info-row {
            flex-direction: column;
        }
        
        .info-label {
            width: 100%;
            margin-bottom: 4px;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .content-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<style>
    /* Mobile Bottom Navigation */
    .mobile-bottom-nav {
        display: none;
    }
    @media (max-width: 1024px) {
        .mobile-bottom-nav {
            display: flex !important;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
            justify-content: space-around;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 -4px 10px rgba(0,0,0,0.1);
        }
        .mobile-bottom-nav a {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 9px;
            padding: 4px 4px;
            border-radius: 8px;
            transition: all 0.2s ease;
            flex: 1;
            text-align: center;
        }
        .mobile-bottom-nav a i {
            font-size: 16px;
            margin-bottom: 2px;
        }
        .mobile-bottom-nav a:hover,
        .mobile-bottom-nav a.active {
            color: white;
            background: rgba(255,255,255,0.15);
        }
        .main-content {
            padding-bottom: 70px !important;
        }
    }
</style>

<div class="container-fluid p-2">
    <!-- Profile Header -->
    <div class="profile-header">
        <a href="profile_edit.php" class="edit-profile-btn">
            <i class="fas fa-edit"></i> Edit Profile
        </a>
        <div class="profile-header-content">
            <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Profile Photo" class="profile-avatar">
            <div class="profile-info">
                <h2><?= htmlspecialchars($teacher['name'] ?? 'Teacher') ?></h2>
                <div class="designation">
                    <i class="fas fa-briefcase"></i> 
                    <?= htmlspecialchars($teacher['designation'] ?? 'Teacher') ?>
                </div>
                <div class="email">
                    <i class="fas fa-envelope"></i>
                    <?= htmlspecialchars($teacher['email'] ?? '') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <h5>Total Classes</h5>
            <h2><?= $class_count ?></h2>
            <div class="stat-icon"><i class="fas fa-school"></i></div>
        </div>

        <div class="stat-card">
            <h5>Total Students</h5>
            <h2><?= $total_students ?></h2>
            <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
        </div>

        <div class="stat-card">
            <h5>Total Teachers</h5>
            <h2><?= $teacher_count ?></h2>
            <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
        </div>

        <div class="stat-card">
            <h5>Your Subjects</h5>
            <h2><?= $subjects->num_rows ?></h2>
            <div class="stat-icon"><i class="fas fa-book"></i></div>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="content-grid">
        <!-- Personal Information -->
        <div class="info-card">
            <h3><i class="fas fa-user"></i> Personal Information</h3>
            <div class="info-row">
                <span class="info-label">Full Name</span>
                <span class="info-value"><?= htmlspecialchars($teacher['name'] ?? 'N/A') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Gender</span>
                <span class="info-value"><?= htmlspecialchars($teacher['gender'] ?? 'N/A') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Date of Birth</span>
                <span class="info-value"><?= htmlspecialchars($teacher['dob'] ?? 'N/A') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Blood Group</span>
                <span class="info-value"><?= htmlspecialchars($teacher['blood_group'] ?? 'N/A') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Religion</span>
                <span class="info-value"><?= htmlspecialchars($teacher['religion'] ?? 'N/A') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Nationality</span>
                <span class="info-value"><?= htmlspecialchars($teacher['nationality'] ?? 'N/A') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">NID</span>
                <span class="info-value"><?= htmlspecialchars($teacher['nid'] ?? 'N/A') ?></span>
            </div>
        </div>

        <!-- Family Information -->
        <div class="info-card">
            <h3><i class="fas fa-users"></i> Family Information</h3>
            <div class="info-row">
                <span class="info-label">Father's Name</span>
                <span class="info-value"><?= htmlspecialchars($teacher['father_name'] ?? 'N/A') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Mother's Name</span>
                <span class="info-value"><?= htmlspecialchars($teacher['mother_name'] ?? 'N/A') ?></span>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="info-card">
            <h3><i class="fas fa-phone"></i> Contact Information</h3>
            <div class="info-row">
                <span class="info-label">Phone</span>
                <span class="info-value"><?= htmlspecialchars($teacher['phone'] ?? 'N/A') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email</span>
                <span class="info-value"><?= htmlspecialchars($teacher['email'] ?? 'N/A') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Present Address</span>
                <span class="info-value"><?= nl2br(htmlspecialchars($teacher['present_address'] ?? 'N/A')) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Permanent Address</span>
                <span class="info-value"><?= nl2br(htmlspecialchars($teacher['permanent_address'] ?? 'N/A')) ?></span>
            </div>
        </div>

        <!-- Professional Information -->
        <div class="info-card">
            <h3><i class="fas fa-graduation-cap"></i> Professional Information</h3>
            <div class="info-row">
                <span class="info-label">Designation</span>
                <span class="info-value"><?= htmlspecialchars($teacher['designation'] ?? 'N/A') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Experience</span>
                <span class="info-value"><?= nl2br(htmlspecialchars($teacher['experience'] ?? 'N/A')) ?></span>
            </div>
        </div>
    </div>

    <!-- Education Section -->
    <div class="info-card" style="margin-bottom: 25px;">
        <h3><i class="fas fa-book-reader"></i> Education</h3>
        <?php if (!empty($teacherEducation) && is_array($teacherEducation)): ?>
            <?php foreach ($teacherEducation as $edu): ?>
                <div class="education-item">
                    <h5><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($edu['education'] ?? '') ?></h5>
                    <?php if (!empty($edu['institute'])): ?>
                        <p><i class="fas fa-university"></i> <?= htmlspecialchars($edu['institute']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($edu['result'])): ?>
                        <p><i class="fas fa-star"></i> Result: <?= htmlspecialchars($edu['result']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-graduation-cap"></i>
                <p>No education details added yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Assigned Subjects -->
    <div class="info-card">
        <h3><i class="fas fa-book"></i> Your Assigned Subjects</h3>
        
        <?php if ($subjects && $subjects->num_rows > 0): ?>
            <?php while ($subject = $subjects->fetch_assoc()): ?>
                <div class="subject-card">
                    <div>
                        <strong><?= htmlspecialchars($subject['name']) ?></strong>
                        <?php if (!empty($subject['code'])): ?>
                            <span class="code">(<?= htmlspecialchars($subject['code']) ?>)</span>
                        <?php endif; ?>
                    </div>
                    <span class="badge-class"><?= htmlspecialchars($subject['class_name']) ?></span>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-book"></i>
                <p>No subjects assigned yet.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

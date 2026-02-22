<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit;
}

$conn->set_charset("utf8mb4");

$batches = $conn->query("SELECT id, name FROM batches ORDER BY name");
$classes = $conn->query("SELECT id, name FROM classes ORDER BY name");

$selected_batch = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;
$selected_class = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$students = [];

// Load all students or filter by batch/class
if ($selected_batch && $selected_class) {
    $students_result = $conn->query("SELECT id, name, roll, father_name, mother_name, father_mobile, mother_mobile, batch_id, class_id FROM students WHERE batch_id = $selected_batch AND class_id = $selected_class ORDER BY roll ASC");
} else {
    // Load all students
    $students_result = $conn->query("SELECT id, name, roll, father_name, mother_name, father_mobile, mother_mobile, batch_id, class_id FROM students ORDER BY roll ASC");
}

if ($students_result) {
    while ($student = $students_result->fetch_assoc()) {
        $students[] = $student;
    }
}

// Get batch and class names for display
function getBatchName($conn, $id) {
    $res = $conn->query("SELECT name FROM batches WHERE id = $id");
    return $res ? $res->fetch_assoc()['name'] : '';
}

function getClassName($conn, $id) {
    $res = $conn->query("SELECT name FROM classes WHERE id = $id");
    return $res ? $res->fetch_assoc()['name'] : '';
}
?>

<style>
    .student-list-container {
        width: 100%;
        padding: 15px;
        margin: 0;
    }

    .page-title {
        margin-bottom: 20px;
        color: #333;
        font-weight: 700;
        font-size: 1.8rem;
    }

    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #e0e0e0;
        display: flex;
        gap: 15px;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
        flex: 1;
        min-width: 150px;
    }

    .form-group label {
        color: #333;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .form-group select {
        padding: 10px 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-family: inherit;
        transition: all 0.3s;
    }

    .form-group select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    .filter-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 10px 25px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .filter-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }

    .students-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }

    .student-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        transition: all 0.3s;
    }

    .student-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .student-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }

    .student-roll {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .student-name {
        font-size: 1.2rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 10px;
    }

    .student-detail {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        align-items: flex-start;
        font-size: 0.9rem;
        color: #666;
    }

    .student-detail i {
        color: #667eea;
        min-width: 20px;
        margin-top: 2px;
    }

    .no-data {
        text-align: center;
        padding: 60px 20px;
        color: #999;
        background: white;
        border-radius: 8px;
    }

    .no-data i {
        font-size: 48px;
        display: block;
        margin-bottom: 15px;
        color: #ddd;
    }

    .result-info {
        padding: 15px;
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        border-radius: 8px;
        color: white;
        margin-bottom: 20px;
    }
    
    .loading {
        text-align: center;
        padding: 40px;
        color: #666;
    }
    
    .loading i {
        font-size: 24px;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        100% { transform: rotate(360deg); }
    }

    .export-btn {
        background: #177a03;
        color: #fff;
        padding: 10px 20px;
        border-radius: 8px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }

    .export-btn:hover {
        background: #145a02;
        box-shadow: 0 6px 16px rgba(23, 122, 3, 0.35);
        transform: translateY(-1px);
    }

    .detect-section {
        margin-top: 25px;
        padding: 15px 20px;
        background: #f9f9f9;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        color: #333;
    }

    .detect-input {
        min-width: 220px;
        padding: 9px 12px;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-family: inherit;
        font-size: 0.9rem;
    }

    .detect-btn {
        background: #005bbb;
        color: #fff;
        border: none;
        padding: 9px 18px;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .detect-btn:hover {
        background: #004799;
    }

    .detect-message {
        width: 100%;
        text-align: center;
        margin-top: 5px;
        color: #666;
        font-size: 0.8rem;
    }

    .highlight-card {
        border-color: #177a03;
        box-shadow: 0 0 0 2px rgba(23, 122, 3, 0.25);
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

<div class="student-list-container">
    <div class="page-title"><i class="fas fa-users"></i> Student List</div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="form-group">
            <label for="batch_id">Batch:</label>
            <select name="batch_id" id="batch_id">
                <option value="0">All Batches</option>
                <?php 
                $batches->data_seek(0);
                while ($b = $batches->fetch_assoc()): ?>
                    <option value="<?= $b['id'] ?>" <?= $selected_batch == $b['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="class_id">Class:</label>
            <select name="class_id" id="class_id">
                <option value="0">All Classes</option>
                <?php 
                $classes->data_seek(0);
                while ($c = $classes->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>" <?= $selected_class == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <button type="button" class="filter-btn" onclick="loadStudents()">
            <i class="fas fa-search"></i> Search
        </button>
        <button type="button" id="exportStudentsBtn" class="export-btn" style="display:none;" onclick="exportStudents()">
            <i class="fas fa-file-csv"></i> Export to Excel (CSV)
        </button>
    </div>

    <!-- Students Display -->
    <div id="students-container">
        <?php if (!empty($students)): ?>
            <div class="result-info">
                <i class="fas fa-check-circle"></i> Found <?= count($students) ?> student(s)
            </div>

            <div class="students-grid">
                <?php foreach ($students as $student): ?>
                    <div class="student-card">
                        <div class="student-card-header">
                            <strong class="student-name"><?= htmlspecialchars($student['name']) ?></strong>
                            <span class="student-roll">Roll: <?= htmlspecialchars($student['roll']) ?></span>
                        </div>

                        <?php 
                        $batch_name = getBatchName($conn, $student['batch_id']);
                        $class_name = getClassName($conn, $student['class_id']);
                        if ($batch_name || $class_name):
                        ?>
                            <div class="student-detail">
                                <i class="fas fa-school"></i>
                                <div>
                                    <?= htmlspecialchars($batch_name) ?> - <?= htmlspecialchars($class_name) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($student['father_name'])): ?>
                            <div class="student-detail">
                                <i class="fas fa-user"></i>
                                <div><strong>Father:</strong> <?= htmlspecialchars($student['father_name']) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($student['mother_name'])): ?>
                            <div class="student-detail">
                                <i class="fas fa-user"></i>
                                <div><strong>Mother:</strong> <?= htmlspecialchars($student['mother_name']) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($student['father_mobile'])): ?>
                            <div class="student-detail">
                                <i class="fas fa-phone"></i>
                                <div><strong>Father:</strong> <?= htmlspecialchars($student['father_mobile']) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($student['mother_mobile'])): ?>
                            <div class="student-detail">
                                <i class="fas fa-phone"></i>
                                <div><strong>Mother:</strong> <?= htmlspecialchars($student['mother_mobile']) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-users"></i>
                <p>Select batch and class to view students</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function loadStudents() {
        const batch_id = $('#batch_id').val();
        const class_id = $('#class_id').val();
        
        $('#students-container').html('<div class="loading"><i class="fas fa-spinner"></i> Loading...</div>');
        
        $.ajax({
            url: 'get_students_list.php',
            type: 'GET',
            data: {
                batch_id: batch_id,
                class_id: class_id
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.count > 0) {
                    let html = '<div class="result-info"><i class="fas fa-check-circle"></i> Found ' + response.count + ' student(s)</div><div class="students-grid">';
                    
                    response.students.forEach(function(student) {
                        let batchClassHtml = (student.batch_id && student.class_id) ? 
                            '<div class="student-detail"><i class="fas fa-school"></i><div>Batch-Class Info</div></div>' : '';
                        let fatherNameHtml = student.father_name ? 
                            '<div class="student-detail"><i class="fas fa-user"></i><div><strong>Father:</strong> ' + student.father_name + '</div></div>' : '';
                        let motherNameHtml = student.mother_name ? 
                            '<div class="student-detail"><i class="fas fa-user"></i><div><strong>Mother:</strong> ' + student.mother_name + '</div></div>' : '';
                        let emailHtml = '';
                        let fatherMobileHtml = student.father_mobile ? 
                            '<div class="student-detail"><i class="fas fa-phone"></i><div><strong>Father:</strong> ' + student.father_mobile + '</div></div>' : '';
                        let motherMobileHtml = student.mother_mobile ? 
                            '<div class="student-detail"><i class="fas fa-phone"></i><div><strong>Mother:</strong> ' + student.mother_mobile + '</div></div>' : '';

                        html += '<div class="student-card">' +
                            '<div class="student-card-header">' +
                            '<strong class="student-name">' + student.name + '</strong>' +
                            '<span class="student-roll">Roll: ' + student.roll + '</span>' +
                            '</div>' +
                            batchClassHtml +
                            fatherNameHtml +
                            motherNameHtml +
                            emailHtml +
                            fatherMobileHtml +
                            motherMobileHtml +
                            '</div>';
                    });
                    
                    html += '</div>';
                    $('#students-container').html(html);
                    if (batch_id !== '0' && class_id !== '0') {
                        $('#exportStudentsBtn').show();
                    } else {
                        $('#exportStudentsBtn').hide();
                    }
                } else {
                    $('#students-container').html('<div class="no-data"><i class="fas fa-users"></i><p>No students found for selected batch and class.</p></div>');
                    $('#exportStudentsBtn').hide();
                }
            },
            error: function() {
                $('#students-container').html('<div class="no-data"><i class="fas fa-exclamation-circle"></i><p>Error loading students. Please try again.</p></div>');
                $('#exportStudentsBtn').hide();
            }
        });
    }

    // Load all students on page load
    $(document).ready(function() {
        loadStudents();
    });

    function exportStudents() {
        const batch_id = $('#batch_id').val();
        const class_id = $('#class_id').val();
        if (batch_id === '0' || class_id === '0') {
            alert('Please select Batch and Class first.');
            return;
        }
        window.location.href = 'export_students_excel.php?batch_id=' + encodeURIComponent(batch_id) + '&class_id=' + encodeURIComponent(class_id);
    }

    function detectStudent() {
        const input = document.getElementById('detect_student_input');
        if (!input) return;

        const query = input.value.trim().toLowerCase();
        const cards = document.querySelectorAll('.student-card');
        const messageEl = document.getElementById('detect_student_message');

        cards.forEach(function(card) {
            card.classList.remove('highlight-card');
        });

        if (!query) {
            if (messageEl) {
                messageEl.textContent = '';
            }
            return;
        }

        let found = false;
        cards.forEach(function(card) {
            if (found) return;
            const nameEl = card.querySelector('.student-name');
            const rollEl = card.querySelector('.student-roll');
            let text = '';
            if (nameEl) text += nameEl.textContent.toLowerCase() + ' ';
            if (rollEl) text += rollEl.textContent.toLowerCase();

            if (text.indexOf(query) !== -1) {
                card.classList.add('highlight-card');
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                found = true;
            }
        });

        if (messageEl) {
            messageEl.textContent = found ? 'Detected matching student.' : 'No matching student found.';
        }
    }
</script>

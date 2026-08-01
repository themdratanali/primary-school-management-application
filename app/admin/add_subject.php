<?php
require_once __DIR__ . '/../../env/session.php';
require_once __DIR__ . '/../../env/config.php';
require_once __DIR__ . '/../../env/csrf.php';

// Auth check
if (!isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

// Ensure classes and subjects tables exist
$conn->query("CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(100),
    total_mark INT,
    class_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$message = "";
$selected_class = isset($_POST['class_id']) ? $_POST['class_id'] : '';

$classes = $conn->query("SELECT * FROM classes ORDER BY name ASC");
$class_count = $classes->num_rows;

if (isset($_POST['submit_add'])) {
    ams_csrf_verify_post();
    $class_id = $_POST['class_id'];
    $name = trim($_POST['name']);
    $code = trim($_POST['code']);
    $total_mark = trim($_POST['total_mark']);

    if ($name != "" && $class_id != "" && $code != "" && $total_mark != "") {
        $check = $conn->prepare("SELECT id FROM subjects WHERE name = ? AND class_id = ?");
        $check->bind_param("si", $name, $class_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $message = "Subject already exists in this class!";
        } else {
            $stmt = $conn->prepare("INSERT INTO subjects (name, code, total_mark, class_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $name, $code, $total_mark, $class_id);
            if ($stmt->execute()) {
                $message = "Subject added successfully!";
                // Keep the selected class after successful submission
            } else {
                $message = "Error: " . $stmt->error;
            }
        }
        $check->close();
    } else {
        $message = "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Subject</title>
    <link rel="shortcut icon" type="image/jpg" href="<?php echo BASE_URL; ?>/uploads/images/এ্যাপেক্স মডেল স্কুল.png"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/dashboard_frame.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/add_subject.css">
</head>

<body>

    <div class="dashboard-title">Add Subject</div>

    <div class="container">
        <div class="left">
            <h2>Add Subject</h2>
            <?php if ($message): ?>
                <p class="message"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>
            <?php if ($class_count > 0): ?>
                <form method="post">
                    <?= ams_csrf_field() ?>
                    <select name="class_id" class="classSelect" id="classSelect" onchange="loadSubjects()" required>
                        <option value="">Select Class</option>
                        <?php $classes->data_seek(0);
                        while ($row = $classes->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>" <?= ($row['id'] == $selected_class) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <input type="text" name="name" placeholder="Enter Subject Name" required />
                    <input type="text" name="code" placeholder="Enter Subject Code" required />
                    <input type="number" name="total_mark" placeholder="Enter Total Mark" required />
                    <button type="submit" name="submit_add">Add Subject</button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">
                    <strong>No classes found!</strong><br>
                    Please add classes first before adding subjects.
                    <br><br>
                    <a href="add_class" class="btn btn-sm btn-primary" target="mainFrame">Go to Add Class</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="right">
            <div class="batch-count" id="subjectCount">
                <?php if ($class_count > 0): ?>
                    Select a class to view subjects
                <?php else: ?>
                    No classes available
                <?php endif; ?>
            </div>
            <div class="batch-list">
                <ul id="subjectList">
                    <?php if ($class_count == 0): ?>
                        <li>Please add classes first.</li>
                    <?php else: ?>
                        <li>Select a class from the dropdown to view subjects.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = <?= json_encode(ams_csrf_token()) ?>;
        
function loadSubjects() {
            const classId = document.getElementById("classSelect").value.trim();
            const countBox = document.getElementById("subjectCount");
            const listBox = document.getElementById("subjectList");

            if (!classId) {
                countBox.textContent = "Select a class to view subjects";
                listBox.innerHTML = "<li>Select a class from the dropdown to view subjects.</li>";
                return;
            }

            countBox.textContent = "Loading...";
            listBox.innerHTML = "<li>Loading...</li>";
            fetch(<?= json_encode(ams_admin_url('get_subjects_class')) ?> + "?class_id=" + encodeURIComponent(classId), { credentials: 'same-origin' })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(function(response) {
                    console.log('[add_subject] Response received:', response);
                    countBox.textContent = "Total Subjects: " + response.count;
                    listBox.innerHTML = "";
                    if (response.count > 0) {
                        response.subjects.forEach(function(sub) {
                            const li = document.createElement("li");
                            li.innerHTML = `
                                <div id="display_${sub.id}" style="width:100%; display:flex; justify-content:space-between; align-items:center; gap:8px;">
                                    <span style="flex-grow:1; font-size:13px;">${sub.name} | Code: ${sub.code} | Mark: ${sub.total_mark}</span>
                                    <div style="display:flex; gap:6px;">
                                        <button class="edit-btn" onclick="editSubject(${sub.id}, '${sub.name.replace(/'/g, "\\'")}', '${sub.code.replace(/'/g, "\\'")}', ${sub.total_mark})">Edit</button>
                                        <button class="cancel-btn" onclick="deleteSubject(${sub.id})">Delete</button>
                                    </div>
                                </div>
                                <div id="edit_${sub.id}" style="display:none; width:100%; gap:8px; align-items:center;">
                                    <div style="display:flex; flex:1; gap:8px; align-items:center; flex-wrap:wrap;">
                                        <input type="text" id="name_${sub.id}" class="inline-input" value="${sub.name}" placeholder="Name" style="flex:1; min-width:100px;">
                                        <input type="text" id="code_${sub.id}" class="inline-input" value="${sub.code}" placeholder="Code" style="flex:1; min-width:100px;">
                                        <input type="number" id="mark_${sub.id}" class="inline-input" value="${sub.total_mark}" placeholder="Mark" style="flex:1; min-width:100px;">
                                        <button class="save-btn" onclick="saveSubject(${sub.id})">Save</button>
                                        <button class="cancel-btn" onclick="cancelEdit(${sub.id})">Cancel</button>
                                    </div>
                                </div>
                            `;
                            listBox.appendChild(li);
                        });
                    } else {
                        listBox.innerHTML = "<li>No subjects found. Add a subject to this class.</li>";
                    }
                })
                .catch(function(err) {
                    console.error('[add_subject] Fetch error:', err);
                    countBox.textContent = "Error loading subjects";
                    listBox.innerHTML = "<li>Error loading subjects. Please try again.</li>";
                });
        }

        // Initial load of subjects
        document.addEventListener('DOMContentLoaded', function() {
            loadSubjects();
        });

        function editSubject(id, name, code, mark) {
            document.getElementById(`display_${id}`).style.display = "none";
            document.getElementById(`edit_${id}`).style.display = "block";
        }

        function cancelEdit(id) {
            document.getElementById(`edit_${id}`).style.display = "none";
            document.getElementById(`display_${id}`).style.display = "block";
        }

        function saveSubject(id) {
            const name = document.getElementById(`name_${id}`).value.trim();
            const code = document.getElementById(`code_${id}`).value.trim();
            const total_mark = document.getElementById(`mark_${id}`).value.trim();

            if (name === "" || code === "" || total_mark === "") {
                alert("All fields are required.");
                return;
            }

            fetch(<?= json_encode(ams_admin_url('update_subject')) ?>, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                credentials: 'same-origin',
                body: "_token=" + encodeURIComponent(csrfToken) + "&id=" + id + "&name=" + encodeURIComponent(name) + "&code=" + encodeURIComponent(code) + "&total_mark=" + total_mark
            })
            .then(response => response.json())
            .then(function(response) {
                alert(response.message);
                if (response.success) {
                    loadSubjects();
                }
            })
            .catch(function() {
                alert("Error updating subject.");
            });
        }

        function deleteSubject(id) {
            if (!confirm("Are you sure you want to delete this subject? This action cannot be undone.")) {
                return;
            }

            fetch(<?= json_encode(ams_admin_url('delete_subject')) ?>, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                credentials: 'same-origin',
                body: "_token=" + encodeURIComponent(csrfToken) + "&id=" + id
            })
            .then(response => response.json())
            .then(function(response) {
                alert(response.message);
                if (response.success) {
                    loadSubjects();
                }
            })
            .catch(function() {
                alert("Error deleting subject.");
            });
        }
</script>
</body>

</html>











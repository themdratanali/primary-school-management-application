<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/csrf.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// Create tables if not exists
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
    <link rel="shortcut icon" type="image/jpg" href="../assets/img/এ্যাপেক্স মডেল স্কুল.png"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard_frame.css">
    <link rel="stylesheet" href="../assets/css/add_subject.css">
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
                    <a href="addclass" class="btn btn-sm btn-primary" target="mainFrame">Go to Add Class</a>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const csrfToken = <?= json_encode(ams_csrf_token()) ?>;
        
        // Auto-load subjects on page load if class is selected
        $(document).ready(function() {
            const selectedClass = $('#classSelect').val();
            if (selectedClass) {
                loadSubjects();
            }
        });

        function loadSubjects() {
            const classId = document.getElementById("classSelect").value;
            const countBox = document.getElementById("subjectCount");
            const listBox = document.getElementById("subjectList");

            if (classId === "") {
                countBox.textContent = "Select a class to view subjects";
                listBox.innerHTML = "<li>Select a class from the dropdown to view subjects.</li>";
                return;
            }

            countBox.textContent = "Loading...";

            $.ajax({
                url: "get_subjects_class.php?class_id=" + classId,
                type: "GET",
                dataType: "json",
                success: function(response) {
                    countBox.textContent = "Total Subjects: " + response.count;
                    listBox.innerHTML = "";
                    if (response.count > 0) {
                        response.subjects.forEach(function(sub) {
                            const li = document.createElement("li");
                            li.innerHTML = `
                                <div id="display_${sub.id}" style="width:100%; display:flex; justify-content:space-between; align-items:center;">
                                    <span style="flex-grow:1;">${sub.name} | Code: ${sub.code} | Mark: ${sub.total_mark}</span>
                                    <div style="display:flex; gap:8px;">
                                        <button class="edit-btn" onclick="editSubject(${sub.id}, '${sub.name}', '${sub.code}', ${sub.total_mark})">Edit</button>
                                        <button class="cancel-btn" onclick="deleteSubject(${sub.id})" >Delete</button>
                                    </div>
                                </div>
                                <div id="edit_${sub.id}" style="display:none; width:100%; gap:8px; align-items:center;">
                                    <div style="display:flex; flex:1; gap:8px; align-items:center;">
                                        <input type="text" id="name_${sub.id}" class="inline-input" value="${sub.name}" placeholder="Name" style="flex:1;">
                                        <input type="text" id="code_${sub.id}" class="inline-input" value="${sub.code}" placeholder="Code" style="flex:1;">
                                        <input type="number" id="mark_${sub.id}" class="inline-input" value="${sub.total_mark}" placeholder="Mark" style="flex:1;">
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
                },
                error: function() {
                    countBox.textContent = "Error loading subjects";
                    listBox.innerHTML = "";
                }
            });
        }

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

            $.ajax({
                url: "update_subject.php",
                type: "POST",
                data: {
                    _token: csrfToken,
                    id: id,
                    name: name,
                    code: code,
                    total_mark: total_mark
                },
                dataType: "json",
                success: function(response) {
                    alert(response.message);
                    if (response.success) {
                        loadSubjects();
                    }
                },
                error: function() {
                    alert("Error updating subject.");
                }
            });
        }

        function deleteSubject(id) {
            if (!confirm("Are you sure you want to delete this subject? This action cannot be undone.")) {
                return;
            }

            $.ajax({
                url: "delete_subject.php",
                type: "POST",
                data: {
                    _token: csrfToken,
                    id: id
                },
                dataType: "json",
                success: function(response) {
                    alert(response.message);
                    if (response.success) {
                        loadSubjects();
                    }
                },
                error: function() {
                    alert("Error deleting subject.");
                }
            });
        }

        $(document).ready(function() {
            if (document.getElementById("classSelect").value !== "") {
                loadSubjects();
            }
        });
    </script>

</body>

</html>

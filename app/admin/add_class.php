<?php

require_once __DIR__ . '/../../env/session.php';
require_once __DIR__ . '/../../env/csrf.php';
include '../../env/config.php';

if (!isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

// Create classes table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$message = "";

if (isset($_POST['submit_add'])) {
    ams_csrf_verify_post();
    $name = trim($_POST['class_name']);
    if ($name !== "") {
        $check = $conn->prepare("SELECT id FROM classes WHERE name = ?");
        $check->bind_param("s", $name);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $message = "Class already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO classes (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                $message = "Class added successfully!";
            } else {
                $message = "Error: " . htmlspecialchars($stmt->error);
            }
        }
        $check->close();
    }
}

if (isset($_POST['submit_edit'])) {
    ams_csrf_verify_post();
    $id = intval($_POST['edit_id']);
    $new_name = trim($_POST['edit_name']);
    if ($new_name !== "") {
        $check = $conn->prepare("SELECT id FROM classes WHERE name = ? AND id != ?");
        $check->bind_param("si", $new_name, $id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $message = "Class name already exists!";
        } else {
            $stmt = $conn->prepare("UPDATE classes SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $new_name, $id);
            if ($stmt->execute()) {
                $message = "Class updated successfully!";
            } else {
                $message = "Error: " . htmlspecialchars($stmt->error);
            }
        }
        $check->close();
    }
}

if (isset($_POST['submit_delete'])) {
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Class deleted successfully!";
    } else {
        $message = "Error: " . htmlspecialchars($stmt->error);
    }
}

$class_stmt = $conn->prepare("SELECT * FROM classes ORDER BY name ASC");
$class_stmt->execute();
$class_result = $class_stmt->get_result();
$class_count = $class_result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Classes</title>
    <link rel="shortcut icon" type="image/jpg" href="<?php echo BASE_URL; ?>/uploads/images/এ্যাপেক্স মডেল স্কুল.png"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/dashboard_frame.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/add_class.css">
    <script>
        function showEdit(id) {
            document.getElementById('display_' + id).style.display = 'none';
            document.getElementById('edit_' + id).style.display = 'flex';
        }

        function hideEdit(id) {
            document.getElementById('display_' + id).style.display = 'flex';
            document.getElementById('edit_' + id).style.display = 'none';
        }
    </script>
</head>

<body>

    <div class="dashboard-title">Manage Classes</div>

    <div class="container">
        <div class="left">
            <h2>Add Class</h2>
            <?php if ($message): ?>
                <div class="message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <form method="post">
                <?= ams_csrf_field() ?>
                <input type="text" name="class_name" placeholder="Enter Class (e.g. Class One)" required>
                <button type="submit" name="submit_add">Add Class</button>
            </form>
        </div>

        <div class="right">
            <h2>Class List (<?= $class_count ?>)</h2>
            <div class="class-list">
                <ul>
                    <?php if ($class_count > 0): ?>
                        <?php while ($row = $class_result->fetch_assoc()): ?>
                            <li>
                                <div id="display_<?= $row['id'] ?>" style="width:100%; display:flex; justify-content:space-between; align-items:center;">
                                    <span style="flex-grow:1;"><?= htmlspecialchars($row['name']) ?></span>
                                    <div style="gap:8px;">
                                        <button type="button" onclick="showEdit(<?= $row['id'] ?>)" class="edit-btn">Edit</button>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this class?');">
                                            <?= ams_csrf_field() ?>
                                            <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="submit_delete" class="cancel-btn">Delete</button>
                                        </form>
                                    </div>
                                </div>
                                <div id="edit_<?= $row['id'] ?>" style="display:none; width:100%; gap:8px; align-items:center;">
                                    <form method="post" style="display:flex; flex:1; gap:8px; align-items:center;">
                                        <?= ams_csrf_field() ?>
                                        <input type="hidden" name="edit_id" value="<?= $row['id'] ?>">
                                        <input type="text" name="edit_name" value="<?= htmlspecialchars($row['name']) ?>" required style="flex:1; padding:8px 10px; border:1px solid #ccc; border-radius:5px; font-size:14px;">
                                        <button type="submit" name="submit_edit" class="save-btn">Save</button>
                                        <button type="button" onclick="hideEdit(<?= $row['id'] ?>)" class="cancel-btn">Cancel</button>
                                    </form>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li>No classes found. Add a class to get started.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

</body>

</html>











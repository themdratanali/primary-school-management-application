<?php
require_once __DIR__ . '/../../env/session.php';
include '../../env/config.php';

// Auth check
if (!isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

// Fetch classes and batches for dropdowns
$classes = $conn->query("SELECT * FROM classes");
$batches = $conn->query("SELECT * FROM batches");

// Handle section creation
if (isset($_POST['submit'])) {
    $class_id = $_POST['class_id'];
    $batch_id = $_POST['batch_id'];
    $name = $_POST['name'];

    $stmt = $conn->prepare("INSERT INTO sections (class_id, batch_id, name) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $class_id, $batch_id, $name);
    $stmt->execute();
    $message = "Section added successfully";
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Add Section</title>
</head>

<body>
    <h2>Add Section</h2>
    <form method="post">
        Class:<br>
        <select name="class_id" required>
            <option value="">Select Class</option>
            <?php while ($row = $classes->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
            <?php endwhile; ?>
        </select><br><br>

        Batch:<br>
        <select name="batch_id" required>
            <option value="">Select Batch</option>
            <?php while ($row = $batches->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
            <?php endwhile; ?>
        </select><br><br>

        Section Name:<br>
        <input type="text" name="name" required><br><br>

        <button type="submit" name="submit">Add Section</button>
    </form>
    <?php if (isset($message)) echo "<p style='color:green;'>$message</p>"; ?>
    <p><a href="<?php echo BASE_URL; ?>/admin/dashboard">Back to Dashboard</a></p>
</body>

</html>








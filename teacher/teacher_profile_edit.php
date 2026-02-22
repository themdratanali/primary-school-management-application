<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: ../admin/login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Teacher ID missing or invalid");
}

$id = (int)$_GET['id'];

$sql = "SELECT * FROM teachers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Teacher not found.");
}

$photo = (!empty($data['photo']) && file_exists($data['photo'])) ? $data['photo'] : '../uploads/teachers/default-photo.jpg';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $mother_name = $_POST['mother_name'] ?? '';
    $father_name = $_POST['father_name'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $blood_group = $_POST['blood_group'] ?? '';
    $religion = $_POST['religion'] ?? '';
    $nationality = $_POST['nationality'] ?? '';
    $nid = $_POST['nid'] ?? '';
    $present_address = $_POST['present_address'] ?? '';
    $permanent_address = $_POST['permanent_address'] ?? '';
    $education = $_POST['education'] ?? '';
    $experience = $_POST['experience'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject_ids = $_POST['subject_ids'] ?? [];

    if (!empty($_FILES['photo']['name'])) {
        $photoName = '../uploads/teachers/' . time() . '_' . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photoName);
    } else {
        $photoName = $data['photo'];
    }

    $update_sql = "UPDATE teachers SET name=?, gender=?, mother_name=?, father_name=?, dob=?, blood_group=?, religion=?, nationality=?, nid=?, present_address=?, permanent_address=?, education=?, experience=?, phone=?, email=?, photo=? WHERE id=?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssssssssssssssi", $name, $gender, $mother_name, $father_name, $dob, $blood_group, $religion, $nationality, $nid, $present_address, $permanent_address, $education, $experience, $phone, $email, $photoName, $id);

    if ($update_stmt->execute()) {
        // Update subjects in junction table
        // First, delete existing subjects for this teacher
        $conn->query("DELETE FROM teacher_subjects WHERE teacher_id = $id");
        
        // Then insert new subjects
        if (!empty($subject_ids) && is_array($subject_ids)) {
            $subject_stmt = $conn->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)");
            foreach ($subject_ids as $subject_id) {
                $subject_id = intval($subject_id);
                $subject_stmt->bind_param("ii", $id, $subject_id);
                $subject_stmt->execute();
            }
            $subject_stmt->close();
        }
        
        header("Location: teacher_profile.php?id=$id");
        exit;
    } else {
        echo "Error updating teacher profile.";
    }
}

$subjects_result = $conn->query("SELECT id, name FROM subjects ORDER BY name");

// Get current subjects for this teacher
$current_subjects_result = $conn->query("SELECT subject_id FROM teacher_subjects WHERE teacher_id = $id");
$current_subjects = [];
while ($row = $current_subjects_result->fetch_assoc()) {
    $current_subjects[] = $row['subject_id'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Teacher Profile - <?= htmlspecialchars($data['name']) ?></title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f0f0;
            margin: 0;
        }

        .container {
            max-width: 700px;
            margin: 30px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
        }

        .container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        form label {
            font-weight: bold;
        }

        form input[type="text"],
        form input[type="date"],
        form input[type="email"],
        form textarea,
        form select {
            width: 100%;
            padding: 8px;
            margin: 5px 0 12px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        form textarea {
            resize: vertical;
        }

        form button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 16px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        form button:hover {
            background: #0056b3;
        }

        .photo-preview {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            display: block;
            margin: 0 auto 15px auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Edit Teacher Profile</h2>
        <img src="<?= htmlspecialchars($photo) ?>" alt="Teacher Photo" class="photo-preview">
        <form method="post" enctype="multipart/form-data">
            <label>Name:</label>
            <input type="text" name="name" value="<?= htmlspecialchars($data['name']) ?>" required>

            <label>Gender:</label>
            <input type="text" name="gender" value="<?= htmlspecialchars($data['gender']) ?>">

            <label>Mother's Name:</label>
            <input type="text" name="mother_name" value="<?= htmlspecialchars($data['mother_name']) ?>">

            <label>Father's Name:</label>
            <input type="text" name="father_name" value="<?= htmlspecialchars($data['father_name']) ?>">

            <label>Date of Birth:</label>
            <input type="date" name="dob" value="<?= htmlspecialchars($data['dob']) ?>">

            <label>Blood Group:</label>
            <input type="text" name="blood_group" value="<?= htmlspecialchars($data['blood_group']) ?>">

            <label>Religion:</label>
            <input type="text" name="religion" value="<?= htmlspecialchars($data['religion']) ?>">

            <label>Nationality:</label>
            <input type="text" name="nationality" value="<?= htmlspecialchars($data['nationality']) ?>">

            <label>NID:</label>
            <input type="text" name="nid" value="<?= htmlspecialchars($data['nid']) ?>">

            <label>Present Address:</label>
            <textarea name="present_address" rows="2"><?= htmlspecialchars($data['present_address']) ?></textarea>

            <label>Permanent Address:</label>
            <textarea name="permanent_address" rows="2"><?= htmlspecialchars($data['permanent_address']) ?></textarea>

            <label>Education:</label>
            <textarea name="education" rows="2"><?= htmlspecialchars($data['education']) ?></textarea>

            <label>Experience:</label>
            <textarea name="experience" rows="2"><?= htmlspecialchars($data['experience']) ?></textarea>

            <label>Phone:</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($data['phone']) ?>">

            <label>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($data['email']) ?>">

            <label>Subjects (Select one or multiple):</label>
            <select name="subject_ids[]" multiple>
                <?php 
                // Fetch subjects fresh for the form
                $subjects_for_form = $conn->query("SELECT id, name FROM subjects ORDER BY name");
                while ($subject = $subjects_for_form->fetch_assoc()): 
                ?>
                    <option value="<?= $subject['id'] ?>" <?= in_array($subject['id'], $current_subjects) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($subject['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <small><em>Hold Ctrl (Cmd on Mac) to select multiple subjects</em></small>

            <label>Change Photo (optional):</label>
            <input type="file" name="photo" accept="image/*">

            <button type="submit">Save Changes</button>
        </form>
    </div>
</body>

</html>

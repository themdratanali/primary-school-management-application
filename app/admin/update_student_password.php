<?php
require_once __DIR__ . '/../../env/session.php';
include '../../env/config.php';

$is_admin = isset($_SESSION['admin']);
$is_student = isset($_SESSION['student_email']);

if (!$is_admin && !$is_student) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password cannot be empty']);
    exit;
}

if ($is_student) {
    $student_main_id = $_SESSION['student_main_id'] ?? 0;
    $student_dynamic_id = $_SESSION['student_dynamic_id'] ?? 0;
    $student_user_id = 0;
    
    $stmt = $conn->prepare("SELECT id FROM student_users WHERE email = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $_SESSION['student_email']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $student_user_id = (int)$res->fetch_assoc()['id'];
        }
        $stmt->close();
    }
    
    if ($id !== $student_user_id) {
        echo json_encode(['success' => false, 'message' => 'You can only update your own password']);
        exit;
    }
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE student_users SET password = ?, plain_password = ? WHERE id = ?");
$stmt->bind_param("ssi", $hashed_password, $password, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update password']);
}

$stmt->close();
$conn->close();





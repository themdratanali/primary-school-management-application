<?php
require_once __DIR__ . '/../includes/session.php';

if (!isset($_SESSION['teacher_id']) && !isset($_SESSION['admin'])) {
    header('Location: ../admin/login.php');
    exit;
}

header('Location: ../student/marksheet.php');
exit;

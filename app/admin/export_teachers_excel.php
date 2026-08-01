<?php
require_once __DIR__ . '/../../env/session.php';
include '../../env/config.php';

if (!isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

// Fetch teachers data with subjects
$sql = "SELECT t.id, t.name, t.phone, t.email, t.present_address, t.permanent_address, t.plain_password,
               t.mother_name, t.father_name, t.gender, t.dob, t.blood_group, t.religion, 
               t.nationality, t.nid, t.education, t.experience,
               GROUP_CONCAT(s.name SEPARATOR ', ') AS subjects
        FROM teachers t
        LEFT JOIN teacher_subjects ts ON t.id = ts.teacher_id
        LEFT JOIN subjects s ON ts.subject_id = s.id
        GROUP BY t.id
        ORDER BY t.name";

$result = $conn->query($sql);

// Create CSV content
$csv_data = array();
$csv_data[] = array(
    'ID', 'Name', 'Phone', 'Email', 'Password', 'Present Address', 'Permanent Address',
    'Mother Name', 'Father Name', 'Gender', 'Date of Birth', 'Blood Group',
    'Religion', 'Nationality', 'NID', 'Education', 'Experience', 'Subjects'
);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $csv_data[] = array(
            $row['id'],
            $row['name'],
            $row['phone'],
            $row['email'],
            $row['plain_password'] ?? '',
            $row['present_address'],
            $row['permanent_address'],
            $row['mother_name'],
            $row['father_name'],
            $row['gender'],
            $row['dob'],
            $row['blood_group'],
            $row['religion'],
            $row['nationality'],
            $row['nid'],
            $row['education'],
            $row['experience'],
            $row['subjects']
        );
    }
}

// Convert array to CSV format
$csv_string = '';
foreach ($csv_data as $row) {
    $csv_string .= '"' . implode('","', $row) . '"' . "\r\n";
}

// Set headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Teachers_' . date('Y-m-d_H-i-s') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Output CSV content
echo $csv_string;
exit;
?>









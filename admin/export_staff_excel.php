<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$conn->set_charset("utf8mb4");

$result = $conn->query("SELECT name, designation, phone FROM staff ORDER BY name");

$csv_data = [];
$csv_data[] = ['Name', 'Designation', 'Phone'];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $csv_data[] = [
            $row['name'],
            $row['designation'],
            $row['phone']
        ];
    }
}

$csv_string = '';
foreach ($csv_data as $row) {
    $escaped = array_map(function ($value) {
        $value = (string)$value;
        $value = str_replace('"', '""', $value);
        return $value;
    }, $row);
    $csv_string .= '"' . implode('","', $escaped) . '"' . "\r\n";
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Staff_' . date('Y-m-d_H-i-s') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

echo $csv_string;
exit;


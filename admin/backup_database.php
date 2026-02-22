<?php

require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

function backupDatabase($conn) {
    $sql_backup = '';
    
    $tables_result = $conn->query("SHOW TABLES");
    $tables = [];
    
    while ($table = $tables_result->fetch_row()) {
        $tables[] = $table[0];
    }
    
    foreach ($tables as $table) {
        $safe_table = '`' . str_replace('`', '``', $table) . '`';
        
        $create_result = $conn->query("SHOW CREATE TABLE {$safe_table}");
        $create_row = $create_result->fetch_row();
        
        $sql_backup .= "\n-- =============================================\n";
        $sql_backup .= "-- Table: {$safe_table}\n";
        $sql_backup .= "-- =============================================\n";
        $sql_backup .= "DROP TABLE IF EXISTS {$safe_table};\n";
        $sql_backup .= $create_row[1] . ";\n\n";
        
        $data_result = $conn->query("SELECT * FROM {$safe_table}");
        
        if ($data_result && $data_result->num_rows > 0) {
            $fields = $data_result->fetch_fields();
            $columns = [];
            foreach ($fields as $field) {
                $columns[] = '`' . str_replace('`', '``', $field->name) . '`';
            }
            
            $sql_backup .= "-- Data for table {$safe_table}\n";
            $sql_backup .= "INSERT INTO {$safe_table} (" . implode(", ", $columns) . ") VALUES\n";
            
            $first_row = true;
            while ($row = $data_result->fetch_assoc()) {
                if (!$first_row) {
                    $sql_backup .= ",\n";
                }
                
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = "NULL";
                    } elseif (is_numeric($value)) {
                        $values[] = $value;
                    } else {
                        $values[] = "'" . $conn->real_escape_string($value) . "'";
                    }
                }
                
                $sql_backup .= "(" . implode(", ", $values) . ")";
                $first_row = false;
            }
            
            $sql_backup .= ";\n\n";
        }
    }
    
    return $sql_backup;
}

if (isset($_GET['download']) && $_GET['download'] === '1') {
    $db_name = 'apex';
    $backup_content = "-- =============================================\n";
    $backup_content .= "-- Database Backup: {$db_name}\n";
    $backup_content .= "-- Backup Date: " . date('Y-m-d H:i:s') . "\n";
    $backup_content .= "-- =============================================\n\n";
    $backup_content .= "-- Create Database\n";
    $backup_content .= "CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
    $backup_content .= "USE `{$db_name}`;\n";
    $backup_content .= backupDatabase($conn);
    
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="apex_backup_' . date('Y-m-d_H-i-s') . '.sql"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo $backup_content;
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Backup</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .backup-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .backup-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        
        .backup-info {
            background: #f0f7ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .backup-info h3 {
            margin: 0 0 10px 0;
            color: #667eea;
        }
        
        .backup-info p {
            margin: 5px 0;
            color: #555;
            font-size: 14px;
        }
        
        .backup-details {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .backup-details p {
            margin: 8px 0;
            font-size: 14px;
        }
        
        .backup-button {
            display: block;
            width: 100%;
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: center;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.3s;
            text-decoration: none;
        }
        
        .backup-button:hover {
            background: #764ba2;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="backup-container">
        <h2>Database Backup</h2>
        
        <div class="backup-info">
            <h3>Apex School Management System</h3>
            <p>Backup your entire database with all tables and data</p>
            <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
        
        <div class="backup-details">
            <p><strong>Database:</strong> apex</p>
            <p><strong>Format:</strong> SQL (SQL Code Format)</p>
            <p><strong>File Type:</strong> .sql</p>
            <p><strong>Contents:</strong> All tables with structure and data</p>
            <p><strong>Size:</strong> Will vary based on data</p>
        </div>
        
        <a href="backup_database.php?download=1" class="backup-button">Download Database Backup (SQL)</a>  
    </div>
</body>
</html>

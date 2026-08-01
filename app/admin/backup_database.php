<?php
require_once __DIR__ . '/../../env/session.php';
include '../../env/config.php';

if (!isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

function backupDatabase($conn)
{
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
    <title>Database Backup - Apex Model School</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/admit_card.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div style="margin: auto;">
        <div class="form-container">
            <div style="background: #fff; border-radius: 8px; padding: 25px;">
                <h2 style="text-align: center; color: #333; margin: 0 0 5px 0; font-size: 20px;">
                    <i class="fas fa-database" style="color: #3498db; margin-right: 8px;"></i> Database Backup
                </h2>
                <p style="text-align: center; color: #888; margin: 0 0 20px 0; font-size: 13px;">
                    Apex Model School &mdash; Backup &amp; Restore Center
                </p>

                <div style="background: #f0f7ff; border-left: 4px solid #3498db; padding: 14px 16px; border-radius: 5px; margin-bottom: 18px;">
                    <h3 style="margin: 0 0 8px 0; color: #3498db; font-size: 14px;">
                        <i class="fas fa-info-circle" style="margin-right: 6px;"></i> About This Backup
                    </h3>
                    <p style="margin: 0 0 4px 0; color: #555; font-size: 13px;">
                        Backup your entire database with all tables and data. This will generate a complete SQL dump of the apex database.
                    </p>
                    <p style="margin: 0; color: #888; font-size: 12px;">
                        Generated on: <?php echo date('Y-m-d H:i:s'); ?>
                    </p>
                </div>

                <div style="background: #f9f9f9; padding: 14px 16px; border-radius: 5px; margin-bottom: 20px;">
                    <p style="margin: 6px 0; font-size: 13px;"><strong><i class="fas fa-server" style="color: #3498db; width: 20px;"></i> Database:</strong> apex</p>
                    <p style="margin: 6px 0; font-size: 13px;"><strong><i class="fas fa-file-code" style="color: #3498db; width: 20px;"></i> Format:</strong> SQL (SQL Code Format)</p>
                    <p style="margin: 6px 0; font-size: 13px;"><strong><i class="fas fa-file-alt" style="color: #3498db; width: 20px;"></i> File Type:</strong> .sql</p>
                    <p style="margin: 6px 0; font-size: 13px;"><strong><i class="fas fa-table" style="color: #3498db; width: 20px;"></i> Contents:</strong> All tables with structure and data</p>
                    <p style="margin: 6px 0; font-size: 13px;"><strong><i class="fas fa-hdd" style="color: #3498db; width: 20px;"></i> Size:</strong> Will vary based on database size</p>
                </div>

                <a href="backup_database.php?download=1" class="generatebutton" style="display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;">
                    <i class="fas fa-download"></i> Download Database Backup (SQL)
                </a>
            </div>
        </div>
    </div>
</body>

</html>
<?php
// Error reporting for debugging
error_reporting(0);
ini_set('display_errors', 0);

include '../../env/config.php';

header('Content-Type: application/json');

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$batch_id = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

$months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
$paid_months = [];

try {
    if ($student_id > 0 && $batch_id > 0 && $class_id > 0) {
        // Get batch name
        $batch_result = $conn->query("SELECT name FROM batches WHERE id = " . (int)$batch_id);
        if ($batch_result && $batch_result->num_rows > 0) {
            $batch_data = $batch_result->fetch_assoc();
            $batch_name = $batch_data['name'];
        } else {
            $batch_name = '';
        }

        $class_result = $conn->query("SELECT name FROM classes WHERE id = " . (int)$class_id);
        if ($class_result && $class_result->num_rows > 0) {
            $class_data = $class_result->fetch_assoc();
            $class_name = $class_data['name'];
        } else {
            $class_name = '';
        }

        // Get class name
        $class_result = $conn->query("SELECT name FROM classes WHERE id = " . (int)$class_id);
        if ($class_result && $class_result->num_rows > 0) {
            $class_data = $class_result->fetch_assoc();
            $class_name = $class_data['name'];
        } else {
            $class_name = '';
        }

        // Sanitize table parts
        $batch_clean = preg_replace('/[^a-zA-Z0-9]/', '_', trim($batch_name));
        $class_clean = preg_replace('/[^a-zA-Z0-9]/', '_', trim($class_name));

        // Look for any monthly fee table that matches the pattern (case insensitive)
        $all_tables = $conn->query("SHOW TABLES");
        $monthly_tables = [];
        while ($table = $all_tables->fetch_array()) {
            $table_name = $table[0];
            // Check if table name contains the batch and class with monthly
            $table_lower = strtolower($table_name);
            $batch_lower = strtolower($batch_clean);
            $class_lower = strtolower($class_clean);
            
            if (stripos($table_lower, $batch_lower) !== false && 
                stripos($table_lower, $class_lower) !== false && 
                stripos($table_lower, 'monthly') !== false) {
                $monthly_tables[] = $table_name;
            }
        }
        
        // Also check for 'fees_' prefixed tables
        $expected_table = "fees_" . $batch_clean . "_" . $class_clean . "_monthly";
        $result = $conn->query("SHOW TABLES LIKE '$expected_table'");
        if ($result && $result->num_rows > 0) {
            $monthly_tables[] = $expected_table;
        }
        
        // Get paid months for this student from all monthly tables
        foreach ($monthly_tables as $table_name) {
            // Get paid months for this student - check all records regardless of year
            $result = $conn->query("SELECT fee_type_detail FROM `$table_name` WHERE student_id = " . (int)$student_id . " AND LOWER(fee_type_category) = 'monthly'");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $paid_month = trim($row['fee_type_detail']);
                    // Normalize month names (case insensitive)
                    foreach ($months as $month) {
                        if (strtolower($paid_month) === strtolower($month)) {
                            $paid_months[] = $month;
                            break;
                        }
                    }
                }
            }
        }

        // Also check legacy tables with different naming conventions
        $legacy_patterns = [
            "fees_{$batch_clean}_{$class_clean}",
            "fees_monthly_{$batch_clean}_{$class_clean}",
            "monthly_fees_{$batch_clean}_{$class_clean}"
        ];

        foreach ($legacy_patterns as $pattern) {
            $result = $conn->query("SHOW TABLES LIKE '$pattern'");
            while ($result && $result->num_rows > 0) {
                $table_name = $pattern;
                $result = $conn->query("SELECT fee_type_detail FROM `$table_name` WHERE student_id = $student_id AND LOWER(fee_type_category) = 'monthly'");
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $paid_month = trim($row['fee_type_detail']);
                        foreach ($months as $month) {
                            if (strtolower($paid_month) === strtolower($month)) {
                                $paid_months[] = $month;
                                break;
                            }
                        }
                    }
                }
                break; // Only check once per pattern
            }
        }
    }

    // Get unpaid months (months that are not in paid_months)
    $unpaid_months = [];
    $paid_months = array_unique($paid_months);
    foreach ($months as $month) {
        if (!in_array($month, $paid_months)) {
            $unpaid_months[] = $month;
        }
    }

    echo json_encode($unpaid_months);
} catch (Exception $e) {
    // Return all months on error (fallback)
    echo json_encode($months);
}




<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

/* ------------------ Admin check ------------------ */
if (!isset($_SESSION['admin'])) {
    header('Location: ../admin/login.php');
    exit;
}

/* ------------------ Auto Reset Receipt on Page Load ------------------ */
// Clear receipt when page loads (fresh start)
unset($_SESSION['fee_receipt']);
unset($_SESSION['receipt_id']);

/* ------------------ Initialize Receipt ------------------ */
$_SESSION['fee_receipt'] = [];
$_SESSION['receipt_id'] = 'RCPT-' . date('Ymd') . '-' . rand(1000, 9999);

/* ------------------ Fee Type Categories ------------------ */
$fee_type_categories = [
    'Monthly' => [
        'label' => 'Monthly Fee',
        'subtype' => 'select_unpaid',
        'options' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        'type' => 'select_unpaid'
    ],
    'Exam' => [
        'label' => 'Exam Fee',
        'subtype' => 'select',
        'options' => ['1st Tutorial', '2nd Tutorial', '3rd Tutorial', '1st Term Exam', '2nd Term Exam', 'Annual Exam'],
        'type' => 'select'
    ],
    'Admission' => [
        'label' => 'Admission Fee',
        'subtype' => 'none',
        'options' => ['New Admission'],
        'type' => 'none'
    ],
    'Transport' => [
        'label' => 'Transport Fee',
        'subtype' => 'none',
        'options' => ['Transport Fee'],
        'type' => 'none'
    ],
    'Sport' => [
        'label' => 'Sport Fee',
        'subtype' => 'none',
        'options' => ['Sport Fee'],
        'type' => 'none'
    ],
    'Study Tour Fee' => [
        'label' => 'Study Tour Fee',
        'subtype' => 'none',
        'options' => ['Study Tour Fee'],
        'type' => 'none'
    ]
];

/* ------------------ Fetch Batches & Classes ------------------ */
$batches = $conn->query("SELECT * FROM batches ORDER BY name");
$classes = $conn->query("SELECT * FROM classes ORDER BY name");

/* ------------------ Get Request Params ------------------ */
$batch_id = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

/* ------------------ Helper Functions ------------------ */
function sanitize_table_part($str)
{
    return preg_replace('/[^a-zA-Z0-9]/', '_', trim($str));
}

/* ------------------ Get Batch & Class Name ------------------ */
$batch_name = '';
$class_name = '';
if ($batch_id > 0) {
    $stmt = $conn->prepare("SELECT name FROM batches WHERE id = ?");
    $stmt->bind_param("i", $batch_id);
    $stmt->execute();
    $stmt->bind_result($batch_name);
    $stmt->fetch();
    $stmt->close();
}
if ($class_id > 0) {
    $stmt = $conn->prepare("SELECT name FROM classes WHERE id = ?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $stmt->bind_result($class_name);
    $stmt->fetch();
    $stmt->close();
}

/* ------------------ Get Students ------------------ */
$students = null;
if ($batch_id > 0 && $class_id > 0) {
    $batch_clean = sanitize_table_part($batch_name);
    $class_clean = sanitize_table_part($class_name);
    $student_table = "Student_{$batch_clean}_{$class_clean}";

    $result = $conn->query("SHOW TABLES LIKE '$student_table'");
    if ($result && $result->num_rows > 0) {
        $sql = "SELECT id, name, roll, photo FROM `$student_table` ORDER BY name ASC";
        $students = $conn->query($sql);
    }
}

/* ------------------ Get Single Student ------------------ */
$student = null;
if ($student_id > 0 && $batch_id > 0 && $class_id > 0) {
    $batch_clean = sanitize_table_part($batch_name);
    $class_clean = sanitize_table_part($class_name);
    $student_table = "Student_{$batch_clean}_{$class_clean}";

    $result = $conn->query("SHOW TABLES LIKE '$student_table'");
    if ($result && $result->num_rows > 0) {
        $stmt = $conn->prepare("SELECT * FROM `$student_table` WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($student) {
            $student['batch_name'] = $batch_name;
            $student['class_name'] = $class_name;
        }
    }
}

/* ------------------ Message ------------------ */
$message = "";

/* ------------------ Process Multiple Fee Submission ------------------ */
if ($student && isset($_POST['fee_entries']) && is_array($_POST['fee_entries'])) {
    $fee_entries = $_POST['fee_entries'];
    
    foreach ($fee_entries as $entry) {
        $fee_type_category = isset($entry['fee_type_category']) ? trim($entry['fee_type_category']) : '';
        $fee_type_detail = isset($entry['fee_type_detail']) ? trim($entry['fee_type_detail']) : '';
        $amount = isset($entry['amount']) ? trim($entry['amount']) : '';
        
        // Skip empty entries
        if (empty($fee_type_category) || empty($fee_type_detail) || $amount === '') {
            continue;
        }
        
        // Check for duplicates
        $duplicate = false;
        foreach ($_SESSION['fee_receipt'] as $item) {
            if ($item['student_id'] == $student_id &&
                $item['fee_type_category'] == $fee_type_category &&
                $item['fee_type_detail'] == $fee_type_detail) {
                $duplicate = true;
                break;
            }
        }
        
        if ($duplicate) {
            continue;
        }

        // Add Fee to Session
        $_SESSION['fee_receipt'][] = [
            'receipt_id' => $_SESSION['receipt_id'],
            'student_id' => $student_id,
            'student_name' => $student['name'],
            'fee_type_category' => $fee_type_category,
            'fee_type_detail' => $fee_type_detail,
            'amount' => $amount,
            'date' => date('Y-m-d H:i:s')
        ];

        // Create Table If Not Exists
        $table_name = "fees_" . sanitize_table_part($batch_name) . "_" . sanitize_table_part($class_name) . "_" . strtolower($fee_type_category);
        $conn->query("CREATE TABLE IF NOT EXISTS `$table_name` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            receipt_id VARCHAR(50),
            student_id INT,
            student_name VARCHAR(100),
            fee_type_category VARCHAR(50),
            fee_type_detail VARCHAR(100),
            amount VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Insert Fee into DB with Receipt ID
        $stmt = $conn->prepare("INSERT INTO `$table_name` (receipt_id, student_id, student_name, fee_type_category, fee_type_detail, amount) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissss", $_SESSION['receipt_id'], $student_id, $student['name'], $fee_type_category, $fee_type_detail, $amount);
        $stmt->execute();
        $stmt->close();

        // Save Fee into CSV
        $csvDir = '../uploads/excel';
        if (!is_dir($csvDir)) mkdir($csvDir, 0777, true);

        $csvFile = $csvDir . "/fee_" . sanitize_table_part($batch_name) . "_" . sanitize_table_part($class_name) . "_" . strtolower($fee_type_category) . ".csv";

        $writeHeader = !file_exists($csvFile);
        $fp = fopen($csvFile, 'a');

        if ($writeHeader) {
            fputcsv($fp, ['Receipt ID', 'Date', 'Student ID', 'Student Name', 'Batch', 'Class', 'Fee Category', 'Fee Detail', 'Amount']);
        }

        fputcsv($fp, [$_SESSION['receipt_id'], date('Y-m-d H:i:s'), $student_id, $student['name'], $batch_name, $class_name, $fee_type_category, $fee_type_detail, $amount]);
        fclose($fp);
    }
    
    $message = "<p class='success'>✅ All fees added successfully!</p>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create Fee Receipt - Apex Model School</title>
    <link rel="stylesheet" href="../assets/css/admit_card.css">
    <link rel="stylesheet" href="../assets/css/fee_receipt.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .fee-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: flex-start;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            flex-wrap: wrap;
        }
        .fee-row .form-group {
            flex: 1;
            min-width: 150px;
            margin-bottom: 0;
        }
        .fee-row .form-group label {
            font-size: 12px;
            color: #666;
            margin-bottom: 3px;
        }
        .fee-row .form-group select,
        .fee-row .form-group input {
            font-size: 14px;
        }
        .remove-row-btn {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            cursor: pointer;
            margin-top: 22px;
        }
        .remove-row-btn:hover {
            background: #c0392b;
        }
        .add-fee-section {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 2px dashed #3498db;
        }
        .add-fee-section h4 {
            margin: 0 0 15px 0;
            color: #3498db;
        }
    </style>
</head>

<body>
    <div class="container-flex">
        <div class="form-container">
            <form method="get" id="selectionForm">
                <div class="form-group">
                    <label>Academic Batch:</label>
                    <select name="batch_id" id="batch_id" onchange="document.getElementById('selectionForm').submit()" class="form-control">
                        <option value="0">-- Select Batch --</option>
                        <?php $batches->data_seek(0);
                        while ($batch = $batches->fetch_assoc()): ?>
                            <option value="<?= $batch['id'] ?>" <?= $batch_id == $batch['id'] ? 'selected' : '' ?>><?= htmlspecialchars($batch['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Class:</label>
                    <select name="class_id" id="class_id" onchange="document.getElementById('selectionForm').submit()" class="form-control">
                        <option value="0">-- Select Class --</option>
                        <?php $classes->data_seek(0);
                        while ($class = $classes->fetch_assoc()): ?>
                            <option value="<?= $class['id'] ?>" <?= $class_id == $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Student:</label>
                    <select name="student_id" id="student_id" onchange="document.getElementById('selectionForm').submit()" required class="form-control select2">
                        <option value="0">-- Select Student --</option>
                        <?php if ($students): while ($stu = $students->fetch_assoc()): ?>
                                <option value="<?= $stu['id'] ?>" <?= $student_id == $stu['id'] ? 'selected' : '' ?>><?= htmlspecialchars($stu['name']) ?> (Roll: <?= htmlspecialchars($stu['roll'] ?? 'N/A') ?>)</option>
                        <?php endwhile;
                        endif; ?>
                    </select>
                </div>
            </form>

            <?= $message ?>

            <?php if ($student): ?>
            <form method="post" id="feeForm">
                <input type="hidden" name="batch_id" value="<?= $batch_id ?>">
                <input type="hidden" name="class_id" value="<?= $class_id ?>">
                <input type="hidden" name="student_id" value="<?= $student_id ?>">
                
                <div class="add-fee-section">
                    <div id="feeRowsContainer">
                        <div class="fee-row" data-row="0">
                            <div class="form-group">
                                <label>Fee Type Category:</label>
                                <select name="fee_entries[0][fee_type_category]" class="form-control fee-category" onchange="updateFeeOptions(0)" required>
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($fee_type_categories as $key => $cat): ?>
                                        <option value="<?= $key ?>"><?= $cat['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group fee-detail-container">
                                <label>Fee Type Detail:</label>
                                <select name="fee_entries[0][fee_type_detail]" class="form-control" required>
                                    <option value="">-- Select Detail --</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Amount (৳):</label>
                                <input type="number" name="fee_entries[0][amount]" placeholder="Amount" class="form-control" min="0" step="0.01" required>
                            </div>
                            <button type="button" class="remove-row-btn" onclick="removeFeeRow(0)" style="display:none;">✕</button>
                        </div>
                    </div>
                    
                    <div style="margin-top: 15px; display: flex; gap: 10px;">
                        <button type="button" onclick="addFeeRow()" class="btn btn-secondary">Add Fee Type</button>
                        <button type="submit" class="btn btn-primary">Receipt</button>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        </div>

        <?php if ($student && !empty($_SESSION['fee_receipt'])): ?>
            <div class="admit-container">
                <div id="feeReceipt" class="receipt-container">
                    <div class="watermark">FEE RECEIPT</div>
                    <div class="card-border">
                        <div class="header">
                            <img src="<?= htmlspecialchars($student['photo'] ?? '../assets/img/logo.png') ?>" alt="Student Photo" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22120%22 viewBox=%220 0 100 120%22%3E%3Crect fill=%22%23ddd%22 width=%22100%22 height=%22120%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2212%22 text-anchor=%22middle%22 fill=%22%23999%22%3ENo Photo%3C/text%3E%3C/svg%3E'" style="
    height: 95px;">
                            <div class="header-center">
                                <h2>Apex Model School</h2>
                                <p style="margin: 2px 0 0 5px; font-size: 14px;">Kharkhari Bypass, Motihar, Paba, Rajshahi</p>
                                <p style="margin: 8px auto 8px auto;padding: 2px;font-size: 14px;border: 0.3px solid #000;align-content: center;width: 30%;border-radius: 4px;font-weight: 600;"><?php $receiptTypes = array_unique(array_column($_SESSION['fee_receipt'], 'fee_type_category')); echo implode(' + ', $receiptTypes); ?></p>
                                <p style="margin: 5px auto 5px auto; font-size: 14px;"><strong>Receipt No:</strong> <?= $_SESSION['receipt_id'] ?></p>
                            </div>
                            <img src="<?php if (file_exists('../logo.png')) { echo '../logo.png'; } else { echo 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22 viewBox=%220 0 100 100%22%3E%3Ccircle cx=%2250%22 cy=%2250%22 r=%2245%22 fill=%22%23e0e0e0%22 stroke=%22%23666%22 stroke-width=%222%22/%3E%3C/svg%3E'; } ?>" alt="School Logo">
                        </div>
                        <hr>
                        <div class="row">
                            <p><strong>Name: </strong> <?= htmlspecialchars($student['name']) ?></p>
                            <p><strong>Roll: </strong> <?= htmlspecialchars($student['roll'] ?? 'N/A') ?></p>
                        </div>
                        <div class="row">
                            <p><strong>Batch: </strong> <?= htmlspecialchars($student['batch_name']) ?></p>
                            <p><strong>Class: </strong> <?= htmlspecialchars($student['class_name']) ?></p>
                        </div>
                        <div class="row">
                            <p><strong>Date: </strong> <?= date('d/m/Y') ?></p>
                            <p><strong>Time: </strong> <?= date('h:i A') ?></p>
                        </div>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 50px;">Sl.</th>
                                    <th>Fee Type</th>
                                    <th>Category</th>
                                    <th style="width: 100px;">Amount (৳)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total = 0;
                                $sl = 1;
                                foreach ($_SESSION['fee_receipt'] as $item):
                                    if ($item['student_id'] != $student_id) continue;
                                    $total += (float)$item['amount'];
                                ?>
                                    <tr>
                                        <td><?= $sl++ ?></td>
                                        <td><?= htmlspecialchars($item['fee_type_detail']) ?></td>
                                        <td><?= htmlspecialchars($item['fee_type_category']) ?></td>
                                        <td><?= htmlspecialchars($item['amount']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="3" style="text-align:right; font-weight:bold;">Total Amount (৳)</td>
                                    <td style="font-weight:bold; font-size:1.1em;"><?= $total ?></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div style="text-align:right; margin-top:25px;"> </br></br>
                            <p>________________________</p>
                            <p><strong>Signature</strong></p>
                        </div>
                        
                        <div class="footer-note" style="margin-top:20px; text-align:center; font-size:12px; color:#666;">
                            <p>This is a computer-generated receipt. Please keep it safe for future reference.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="text-align:center; margin-top:20px;">
                <button onclick="downloadFeeReceipt()" class="btn btn-success">Download PDF</button>
            </div>

        <?php elseif ($student): ?>
            <div class="admit-container">
                <div class="empty-receipt">
                    <div class="empty-icon">📋</div>
                    <h3>No Fees Added Yet</h3>
                    <p>Select fee types above and add them to create a receipt</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Fee types configuration
        const feeTypes = {
            'Monthly': {
                subtype: 'select_unpaid',
                options: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
            },
            'Exam': {
                subtype: 'select',
                options: ['1st Tutorial', '2nd Tutorial', '3rd Tutorial', '1st Term Exam', '2nd Term Exam', 'Annual Exam']
            },
            'Admission': {
                subtype: 'none',
                options: ['New Admission']
            },
            'Transport': {
                subtype: 'none',
                options: ['Transport Fee']
            },
            'Sport': {
                subtype: 'none',
                options: ['Sport Fee']
            },
            'Study Tour Fee': {
                subtype: 'none',
                options: ['Study Tour Fee']
            }
        };

        let rowCounter = 0;

        // Initialize Select2 on page load
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true
            });
        });

        function updateFeeOptions(rowId) {
            const container = document.querySelector(`.fee-row[data-row="${rowId}"] .fee-detail-container`);
            const categorySelect = document.querySelector(`.fee-row[data-row="${rowId}"] .fee-category`);
            const type = categorySelect.value;
            
            if (!type) {
                container.innerHTML = '<label>Fee Type Detail:</label><select name="fee_entries[' + rowId + '][fee_type_detail]" class="form-control" required><option value="">-- Select Detail --</option></select>';
                return;
            }
            
            const currentType = feeTypes[type];
            
            if (currentType.subtype === 'none') {
                container.innerHTML = '<label>Fee Type Detail:</label><select name="fee_entries[' + rowId + '][fee_type_detail]" class="form-control" required><option value="' + currentType.options[0] + '">' + currentType.options[0] + '</option></select>';
                return;
            }
            
            if (currentType.subtype === 'select_unpaid') {
                const student_id = document.querySelector('select[name="student_id"]').value;
                const batch_id = document.querySelector('select[name="batch_id"]').value;
                const class_id = document.querySelector('select[name="class_id"]').value;
                
                if (student_id == "0" || batch_id == "0" || class_id == "0") {
                    container.innerHTML = '<p style="color: #e74c3c; font-size: 13px;">⚠️ Please select student, batch, and class first.</p>';
                    return;
                }
                
                container.innerHTML = '<label>Fee Type Detail:</label><select name="fee_entries[' + rowId + '][fee_type_detail]" class="form-control" required><option value="">-- Loading --</option></select>';
                
                fetch('get_unpaid_months.php?student_id=' + student_id + '&batch_id=' + batch_id + '&class_id=' + class_id)
                    .then(response => response.json())
                    .then(months => {
                        if (months.length > 0) {
                            let options = '<option value="">-- Select Month --</option>';
                            months.forEach(m => {
                                options += '<option value="' + m + '">' + m + '</option>';
                            });
                            container.innerHTML = '<label>Fee Type Detail:</label><select name="fee_entries[' + rowId + '][fee_type_detail]" class="form-control" required>' + options + '</select>';
                            $('.select2').select2({width: '100%', placeholder: 'Select a month', allowClear: true});
                        } else {
                            container.innerHTML = '<p style="color: #27ae60; font-weight: bold; padding: 10px; background: #d4edda; border-radius: 5px; font-size: 13px;">✅ All monthly fees have been paid!</p>';
                        }
                    })
                    .catch(error => {
                        let options = '<option value="">-- Select Month --</option>';
                        currentType.options.forEach(m => {
                            options += '<option value="' + m + '">' + m + '</option>';
                        });
                        container.innerHTML = '<label>Fee Type Detail:</label><select name="fee_entries[' + rowId + '][fee_type_detail]" class="form-control" required>' + options + '</select>';
                        $('.select2').select2({width: '100%', placeholder: 'Select a month', allowClear: true});
                    });
                return;
            }
            
            // Regular select
            let options = '<option value="">-- Select --</option>';
            currentType.options.forEach(opt => {
                options += '<option value="' + opt + '">' + opt + '</option>';
            });
            container.innerHTML = '<label>Fee Type Detail:</label><select name="fee_entries[' + rowId + '][fee_type_detail]" class="form-control" required>' + options + '</select>';
            $('.select2').select2({width: '100%', placeholder: 'Select an option', allowClear: true});
        }

        function addFeeRow() {
            rowCounter++;
            const container = document.getElementById('feeRowsContainer');
            const newRow = document.createElement('div');
            newRow.className = 'fee-row';
            newRow.setAttribute('data-row', rowCounter);
            newRow.innerHTML = `
                <div class="form-group">
                    <label>Fee Type Category:</label>
                    <select name="fee_entries[${rowCounter}][fee_type_category]" class="form-control fee-category" onchange="updateFeeOptions(${rowCounter})" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($fee_type_categories as $key => $cat): ?>
                            <option value="<?= $key ?>"><?= $cat['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group fee-detail-container">
                    <label>Fee Type Detail:</label>
                    <select name="fee_entries[${rowCounter}][fee_type_detail]" class="form-control" required>
                        <option value="">-- Select Detail --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount (৳):</label>
                    <input type="number" name="fee_entries[${rowCounter}][amount]" placeholder="Amount" class="form-control" min="0" step="0.01" required>
                </div>
                <button type="button" class="remove-row-btn" onclick="removeFeeRow(${rowCounter})">✕</button>
            `;
            container.appendChild(newRow);
        }

        function removeFeeRow(rowId) {
            const row = document.querySelector(`.fee-row[data-row="${rowId}"]`);
            if (row) {
                row.remove();
            }
        }

        function downloadFeeReceipt() {
            const element = document.getElementById('feeReceipt');
            const receiptId = '<?= $_SESSION['receipt_id'] ?>';
            
            const opt = {
                margin: 0.3,
                filename: `fee_receipt_${receiptId}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
            };
            
            html2pdf().set(opt).from(element).save().then(() => {
                // Auto refresh after download to create new receipt
                setTimeout(() => {
                    window.location.href = window.location.pathname;
                }, 1000);
            });
        }
    </script>
</body>

</html>

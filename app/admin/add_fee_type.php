<?php
require_once __DIR__ . '/../../env/session.php';
include '../../env/config.php';

// Auth check
if (!isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('login'));
    exit;
}

// Fetch batches and classes for dropdowns
$batches = $conn->query("SELECT id, name FROM batches ORDER BY name");
$classes = $conn->query("SELECT id, name FROM classes ORDER BY name");

// Fee type categories configuration
$fee_type_options = [
    'Monthly Fee' => [
        'label' => 'Monthly Fee',
        'default_amount' => 500,
        'description' => 'Regular monthly tuition fees'
    ],
    'Exam Fee' => [
        'label' => 'Exam Fee',
        'default_amount' => 300,
        'description' => 'Tutorial and Term Exam fees'
    ],
    'Admission Fee' => [
        'label' => 'Admission Fee',
        'default_amount' => 5000,
        'description' => 'New student admission'
    ],
    'Transport Fee' => [
        'label' => 'Transport Fee',
        'default_amount' => 800,
        'description' => 'Bus/van transportation'
    ],
    'Sport Fee' => [
        'label' => 'Sport Fee',
        'default_amount' => 400,
        'description' => 'Sports and athletics'
    ],
    'Study Tour Fee' => [
        'label' => 'Study Tour Fee',
        'default_amount' => 1000,
        'description' => 'Educational tours and trips'
    ]
];

$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $batch_id = intval($_POST['batch_id']);
    $class_id = intval($_POST['class_id']);
    $fee_type = $_POST['fee_type'] ?? '';
    $amount = floatval($_POST['amount']);
    $description = $_POST['description'] ?? '';
    
    if ($batch_id && $class_id && $fee_type && $amount > 0) {
        $stmt = $conn->prepare("INSERT INTO fee_types (batch_id, class_id, name, amount, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iidss", $batch_id, $class_id, $fee_type, $amount, $description);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>✅ Fee type added successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>❌ Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-warning'>❌ Please fill all fields with valid data.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Add Fee Type - Apex Model School</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/add_fee_type.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeeba);
            color: #856404;
            border: 1px solid #ffeeba;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="page-header">
            <div class="header-content">
                <h1>💰 Add Fee Type</h1>
                <p>Configure fee types for different categories</p>
            </div>
            <div class="header-icon">
                <i class="fas fa-tags"></i>
            </div>
        </div>

        <?= $message ?>
        
        <form method="post" class="fee-form">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Select Batch:</label>
                        <select name="batch_id" class="form-select select2" required>
                            <option value="">-- Select Batch --</option>
                            <?php while ($b = $batches->fetch_assoc()): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Select Class:</label>
                        <select name="class_id" class="form-select select2" required>
                            <option value="">-- Select Class --</option>
                            <?php while ($c = $classes->fetch_assoc()): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Select Fee Type:</label>
                <select name="fee_type" id="fee_type" class="form-select select2" required>
                    <option value="">-- Select Fee Type --</option>
                    <?php foreach ($fee_type_options as $key => $type): ?>
                        <option value="<?= htmlspecialchars($key) ?>" data-amount="<?= $type['default_amount'] ?>" data-desc="<?= htmlspecialchars($type['description']) ?>">
                            <?= htmlspecialchars($type['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Amount (Tk):</label>
                <input type="number" name="amount" id="amount" step="0.01" min="0" required placeholder="Enter fee amount">
            </div>

            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" id="description" rows="3" placeholder="Enter fee description (optional)"></textarea>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-primary">➕ Add Fee Type</button>
                <button type="reset" class="btn btn-secondary">🔄 Reset Form</button>
            </div>
        </form>

        <!-- Fee Type Info Cards -->
        <div class="fee-info-section">
            <h3>📋 Available Fee Categories</h3>
            <div class="info-cards">
                <?php foreach ($fee_type_options as $key => $type): ?>
                    <div class="info-card" style="--card-color: <?= getCategoryColor($key) ?>">
                        <div class="card-header">
                            <i class="fas fa-tag"></i>
                            <span><?= htmlspecialchars($type['label']) ?></span>
                        </div>
                        <div class="card-body">
                            <p><?= htmlspecialchars($type['description']) ?></p>
                            <span class="default-amount">Default: ৳<?= number_format($type['default_amount'], 2) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(function() {
            // Initialize Select2
            $('.select2').select2({
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true
            });

            // Auto-fill amount and description when fee type is selected
            $('#fee_type').on('change', function() {
                const selected = $(this).find('option:selected');
                const amount = selected.data('amount');
                const desc = selected.data('desc');
                
                if (amount) {
                    $('#amount').val(amount);
                }
                if (desc) {
                    $('#description').val(desc);
                }
            });
        });

        function getCategoryColor(type) {
            const colors = {
                'Monthly Fee': '#3498db',
                'Exam Fee': '#e74c3c',
                'Admission Fee': '#27ae60',
                'Transport Fee': '#f39c12',
                'Sport Fee': '#9b59b6',
                'Study Tour Fee': '#1abc9c'
            };
            return colors[type] || '#667eea';
        }
    </script>
</body>

</html>










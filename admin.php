<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

session_start();

// Helper: get all unique product types/specs from DB
function getAllProductTypes() {
    $db = getDB();
    $types = $db->query("SELECT DISTINCT product_type FROM export_entries")->fetchAll(PDO::FETCH_COLUMN);
    // These are base types always available in dropdown, even if no entries exist yet for them.
    $default_types = ['6KCH', '9KCH']; 
    return array_unique(array_merge($default_types, $types));
}
function getAllSpecifications() {
    $db = getDB();
    $specs = $db->query("SELECT DISTINCT specification FROM export_entries")->fetchAll(PDO::FETCH_COLUMN);
    // These are base specifications always available in dropdown.
    $default_specs = ['40ft', '20ft'];
    return array_unique(array_merge($default_specs, $specs));
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($_POST['username'] === ADMIN_USERNAME && verifyAdminPassword($_POST['password'])) {
        $_SESSION['admin_logged_in'] = true;
        session_regenerate_id(true);
        redirect('admin.php'); // Redirect after successful login
    } else {
        $login_error = "Invalid credentials";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('admin.php');
}

$is_logged_in = isAdmin();

// Fetch entries and ensure they are processed by calculateExportPricing for display
$raw_entries = $is_logged_in ? getExportEntries() : [];
$entries = [];
if ($is_logged_in) {
    foreach ($raw_entries as $rentry) {
        $entries[] = calculateExportPricing($rentry); // Process each entry
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_entry'])) {
    $data_to_save = [
        'entry_date' => $_POST['entry_date'],
        'product_type' => $_POST['product_type'] === '__new__' ? trim($_POST['new_product_type']) : $_POST['product_type'],
        'specification' => $_POST['specification'] === '__new__' ? trim($_POST['new_specification']) : $_POST['specification'],
        'ex_mill_price' => (float)($_POST['ex_mill_price'] ?? 0),
        'comm_rate' => (float)($_POST['comm_rate'] ?? 0) / 100, // Convert percentage to decimal
        'qty_kgs' => (float)($_POST['qty_kgs'] ?? 0),
        'local_freight_inr_total' => (float)($_POST['local_freight_inr_total'] ?? 0),
        'other_exp_input' => (float)($_POST['other_exp_input'] ?? 0),
        'lc_terms_days' => (float)($_POST['lc_terms_days'] ?? 0),
        'exchange_rate' => (float)($_POST['exchange_rate'] ?? 0),
        'margin_rate' => (float)($_POST['margin_rate'] ?? 0) / 100, // Convert percentage to decimal
        'drawback_rate' => (float)($_POST['drawback_rate'] ?? 0) / 100, // Convert percentage to decimal
        'focus_mkt_scheme_rate' => (float)($_POST['focus_mkt_scheme_rate'] ?? 0) / 100, // Convert percentage to decimal
        'ocean_freight_usd_total' => (float)($_POST['ocean_freight_usd_total'] ?? 0)
    ];

    if (!isset($_POST['admin_password']) || !verifyAdminPassword($_POST['admin_password'])) {
        $_SESSION['error'] = "Invalid admin password for saving entry.";
    } else {
        if (saveExportEntry($data_to_save)) {
            $_SESSION['success'] = "Entry saved successfully!";
        } else {
            $_SESSION['error'] = "Failed to save entry. Check logs.";
        }
    }
    redirect('admin.php'); // Redirect to refresh and clear POST data
}

if ($is_logged_in && isset($_GET['export']) && $_GET['export'] === '1') {
    try {
        if (ob_get_length()) ob_clean();
        $entries = getExportEntries();
        if (!empty($entries)) {
            $filename = 'export_' . date('Ymd_His') . '.csv';
            exportToCSV($entries, $filename);
            exit;
        } else {
            $_SESSION['error_message'] = "No entries to export.";
        }
    } catch (PDOException $e) {
        error_log("Export failed: " . $e->getMessage());
        $_SESSION['error_message'] = "Database error during export.";
    }
    redirect('admin.php');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Export Pricing</title>
    <?php echo getCSS(); ?>
    <style>
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .btn-create {
            background: #4cc9f0;
            color: #212529;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
        }
        .btn-create:hover {
            background: #4361ee;
            color: #fff;
        }
        .data-table th, .data-table td {
            text-align: center;
        }
        .data-table th {
            background: #4361ee;
            color: #fff;
        }
        .data-table tr:nth-child(even) {
            background: #f1f3fa;
        }
        .data-table tr:hover {
            background: #e0e7ff;
        }
        .highlight {
            font-weight: bold;
            color: #3f37c9;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 10px;
        }
        h1, h2 {
            color: #3f37c9;
        }
        .nav {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .btn {
            background: #4361ee;
            color: #fff;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
        }
        .btn:hover {
            background: #3f37c9;
        }
        .card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(67,97,238,0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 700px) {
            .container { padding: 5px; }
            .card { padding: 1rem; }
            .data-table th, .data-table td { font-size: 0.9em; padding: 0.5rem; }
        }
        /* Add more specific styles for admin panel */
        .admin-container { max-width: 1400px; margin: auto; padding: 20px; }
        .form-section { background: #f9f9f9; border: 1px solid #eee; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .form-section h3 { margin-top: 0; color: #337ab7; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        .form-grid .form-group { display: flex; flex-direction: column; }
        .form-grid label { font-weight: bold; margin-bottom: 5px; font-size: 0.9em; }
        .form-grid input[type='text'], .form-grid input[type='number'], .form-grid input[type='date'], .form-grid select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
        }
        .form-group input[type='password'] { width: auto; }
        .btn-submit-entry { background-color: #5cb85c; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 1.1em;}
        .btn-submit-entry:hover { background-color: #4cae4c; }
        .existing-entries-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .existing-entries-table th, .existing-entries-table td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size:0.9em; }
        .existing-entries-table th { background-color: #f2f2f2; color: #333; }
        .existing-entries-table tr:nth-child(even) { background-color: #f9f9f9; }
        .existing-entries-table .actions a { margin-right: 5px; text-decoration: none; padding: 5px 8px; border-radius: 3px;}
        .actions .btn-edit { background-color: #f0ad4e; color:white; }
        .actions .btn-delete { background-color: #d9534f; color:white; }
    </style>
    <script>
    function handleTypeChange(sel) {
        document.getElementById('new_product_type_group').style.display = sel.value === '__new__' ? 'flex' : 'none';
    }
    function handleSpecChange(sel) {
        document.getElementById('new_specification_group').style.display = sel.value === '__new__' ? 'flex' : 'none';
    }
    </script>
</head>
<body>
    <div class="admin-container">
        <?php if (!$is_logged_in): ?>
            <div class="card">
                <h1>Admin Login</h1>
                <?php if (isset($login_error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($login_error) ?></div>
                <?php endif; ?>
                <form method="post" action="admin.php">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" name="username" id="username" value="<?= htmlspecialchars(ADMIN_USERNAME) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" name="password" id="password" required>
                    </div>
                    <button type="submit" name="login">Login</button>
                </form>
            </div>
        <?php else: ?>
            <div class="admin-header">
                <h1>Export Pricing Admin Panel</h1>
                <a href="#add-entry-form" class="btn-create">+ Create New Entry</a>
            </div>
            <div class="nav">
                <a href="index.php" class="btn">View User Page</a>
                <a href="admin.php?export=1" class="btn">Export to CSV</a>
                <a href="?logout" class="btn btn-danger">Logout</a>
            </div>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php elseif (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['pdo_error'])): ?>
                <div class="alert alert-danger"><strong>Database Error:</strong> <?= htmlspecialchars($_SESSION['pdo_error']); unset($_SESSION['pdo_error']); ?></div>
            <?php endif; ?>

            <div class="card" id="add-entry-form">
                <h2>Add New Pricing Entry</h2>
                <form method="post" action="admin.php">
                    <div class="form-section">
                        <h3>Basic Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="entry_date">Entry Date:</label>
                                <input type="date" name="entry_date" id="entry_date" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="product_type">Product Type:</label>
                                <select name="product_type" id="product_type" required onchange="handleTypeChange(this)">
                                    <?php foreach (getAllProductTypes() as $type): ?><option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option><?php endforeach; ?>
                                    <option value="__new__">+ Add new...</option>
                                </select>
                            </div>
                            <div class="form-group" id="new_product_type_group" style="display:none;">
                                <label for="new_product_type">New Product Type Name:</label>
                                <input type="text" name="new_product_type" id="new_product_type">
                            </div>
                            <div class="form-group">
                                <label for="specification">Specification:</label>
                                <select name="specification" id="specification" required onchange="handleSpecChange(this)">
                                    <?php foreach (getAllSpecifications() as $spec): ?><option value="<?= htmlspecialchars($spec) ?>"><?= htmlspecialchars($spec) ?></option><?php endforeach; ?>
                                    <option value="__new__">+ Add new...</option>
                                </select>
                            </div>
                            <div class="form-group" id="new_specification_group" style="display:none;">
                                <label for="new_specification">New Specification Name:</label>
                                <input type="text" name="new_specification" id="new_specification">
                            </div>
                             <div class="form-group">
                                <label for="qty_kgs">Quantity (Kgs):</label>
                                <input type="number" name="qty_kgs" id="qty_kgs" step="0.01" required placeholder="e.g., 20412">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Core Pricing & Costs (INR)</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="ex_mill_price">Ex Mill Price (per Unit):</label>
                                <input type="number" name="ex_mill_price" id="ex_mill_price" step="0.01" required placeholder="e.g., 150">
                            </div>
                            <div class="form-group">
                                <label for="local_freight_inr_total">Local Freight (Total INR):</label>
                                <input type="number" name="local_freight_inr_total" id="local_freight_inr_total" step="0.01" required placeholder="e.g., 75000">
                            </div>
                            <div class="form-group">
                                <label for="other_exp_input">Other Expenses (per Unit INR):</label>
                                <input type="number" name="other_exp_input" id="other_exp_input" step="0.01" required placeholder="e.g., 1.00">
                            </div>
                             <div class="form-group">
                                <label for="lc_terms_days">L/C Terms (Days):</label> 
                                <input type="number" name="lc_terms_days" id="lc_terms_days" step="1" required placeholder="e.g., 90">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Rates & Charges</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="comm_rate">Commission Rate (%):</label>
                                <input type="number" name="comm_rate" id="comm_rate" step="0.01" required placeholder="e.g., 1 for 1%">
                            </div>
                            <div class="form-group">
                                <label for="margin_rate">Margin Rate (%):</label>
                                <input type="number" name="margin_rate" id="margin_rate" step="0.01" required placeholder="e.g., 1 for 1%">
                            </div>
                            <div class="form-group">
                                <label for="drawback_rate">Drawback Rate (%):</label>
                                <input type="number" name="drawback_rate" id="drawback_rate" step="0.01" required placeholder="e.g., 2 for 2%">
                            </div>
                            <div class="form-group">
                                <label for="focus_mkt_scheme_rate">Focus Market Scheme Rate (%):</label>
                                <input type="number" name="focus_mkt_scheme_rate" id="focus_mkt_scheme_rate" step="0.01" value="0" placeholder="e.g., 0 for 0%">
                            </div>
                            <div class="form-group">
                                <label for="exchange_rate">Exchange Rate (INR/USD):</label>
                                <input type="number" name="exchange_rate" id="exchange_rate" step="0.0001" required placeholder="e.g., 84.50">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Freight (Overseas)</h3>
                         <div class="form-grid">
                            <div class="form-group">
                                <label for="ocean_freight_usd_total">Ocean Freight (Total USD):</label>
                                <input type="number" name="ocean_freight_usd_total" id="ocean_freight_usd_total" step="0.01" value="0" placeholder="e.g., 0 if not applicable">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_password">Admin Password (to confirm):</label>
                        <input type="password" name="admin_password" id="admin_password" required>
                    </div>
                    <button type="submit" name="save_entry" class="btn-submit-entry">Save Entry</button>
                </form>
            </div>
            <div class="card">
                <h2>Existing Entries</h2>
                <table class="existing-entries-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Spec</th>
                            <th>Ex Mill (₹)</th>
                            <th>Qty (Kg)</th>
                            <th>Comm (₹)</th> 
                            <th>FOB (₹)</th>
                            <th>Net Price (₹/kg)</th>
                            <th>Final Price (₹/kg)</th>
                            <th>Final Price ($/kg)</th>
                            <th>Final Price ($ Total)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($entries)): ?>
                            <?php foreach ($entries as $entry_data): ?>
                                <?php 
                                // Ensure all calculated fields are present, recalculate if necessary 
                                // This is useful if some DB entries are old and missing new C* fields
                                // or if you want to always show the freshest calculation.
                                $display_entry = calculateExportPricing($entry_data); 
                                ?>
                                <tr>
                                    <td><?= formatDate($display_entry['entry_date']) ?></td>
                                    <td><?= htmlspecialchars($display_entry['product_type']) ?></td>
                                    <td><?= htmlspecialchars($display_entry['specification']) ?></td>
                                    <td><?= formatCurrency($display_entry['ex_mill_price'], '₹') ?></td>
                                    <td><?= number_format((float)($display_entry['qty_kgs'] ?? 0), 2) ?></td>
                                    <td><?= formatCurrency($display_entry['C1_comm_value'] ?? 0, '₹') ?></td>
                                    <td><?= formatCurrency($display_entry['C12_fob_value'] ?? 0, '₹') ?></td>
                                    <td><?= formatCurrency($display_entry['C16_net_price_inr_kgs'] ?? 0, '₹') ?></td>
                                    <td><?= formatCurrency($display_entry['C18_final_price_inr'] ?? 0, '₹') ?></td>
                                    <td><?= formatCurrency($display_entry['C19_final_price_usd_per_kg'] ?? 0, '$', 4) ?></td>
                                    <td><?= formatCurrency($display_entry['C19_final_price_usd_total'] ?? 0, '$') ?></td>
                                    <td class="actions">
                                        <a href="edit.php?id=<?= $display_entry['id'] ?? '' ?>" class="btn btn-edit">Edit</a>
                                        <a href="delete.php?id=<?= $display_entry['id'] ?? '' ?>" class="btn btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="12">No entries found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card">
                <h2>Change Admin Password</h2>
                <form method="post" action="admin.php">
                    <div class="form-group">
                        <label for="current_password">Current Password:</label>
                        <input type="password" name="current_password" id="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" name="new_password" id="new_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

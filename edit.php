<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

session_start();

if (!isAdmin()) {
    redirect('admin.php');
}

$id = $_GET['id'] ?? 0;
$db = getDB();
$entry = $db->query("SELECT * FROM export_entries WHERE id = " . (int)$id)->fetch(PDO::FETCH_ASSOC);

if (!$entry) {
    $_SESSION['error_message'] = "Entry not found";
    redirect('admin.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_entry'])) {
    $data = [
        'id' => $id,
        'product_type' => $_POST['product_type'],
        'specification' => $_POST['specification'],
        'ex_mill' => (float)$_POST['ex_mill'],
        'qty_kgs' => (float)$_POST['qty_kgs'],
        'local_freight' => (float)$_POST['local_freight'],
        'other_exp' => (float)$_POST['other_exp'],
        'lc_terms' => (float)$_POST['lc_terms'],
        'exchange_rate' => (float)$_POST['exchange_rate'],
        'margin' => (float)$_POST['margin'] / 100,
        'drawback' => (float)$_POST['drawback'] / 100,
        'focus_mkt_scheme' => (float)$_POST['focus_mkt_scheme'] / 100,
        'ocean_freight_usd' => (float)$_POST['ocean_freight_usd'],
        'entry_date' => $_POST['entry_date']
    ];
    
    $calculated = calculateExportPricing($data);
    
    $stmt = $db->prepare("UPDATE export_entries SET
        product_type = :product_type,
        specification = :specification,
        ex_mill = :ex_mill,
        qty_kgs = :qty_kgs,
        local_freight = :local_freight,
        other_exp = :other_exp,
        lc_terms = :lc_terms,
        exchange_rate = :exchange_rate,
        margin = :margin,
        drawback = :drawback,
        focus_mkt_scheme = :focus_mkt_scheme,
        ocean_freight_usd = :ocean_freight_usd,
        entry_date = :entry_date,
        comm = :comm,
        local_freight_per_kg = :local_freight_per_kg,
        lc_terms_value = :lc_terms_value,
        margin_value = :margin_value,
        fob_value = :fob_value,
        drawback_value = :drawback_value,
        focus_mkt_scheme_value = :focus_mkt_scheme_value,
        net_price_inr = :net_price_inr,
        ocean_freight_per_kg = :ocean_freight_per_kg,
        final_price_inr = :final_price_inr,
        final_price_usd = :final_price_usd
        WHERE id = :id");
    
    $params = array_merge($data, $calculated);
    
    if ($stmt->execute($params)) {
        $_SESSION['success_message'] = "Entry updated successfully!";
        redirect('admin.php');
    } else {
        $error_message = "Failed to update entry. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Entry</title>
    <?php echo getCSS(); ?>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="admin.php">← Back to Admin</a>
        </div>
        
        <h1>Edit Entry</h1>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>
        
        <div class="card">
            <form method="post">
                <div class="form-group">
                    <label for="product_type">Product Type:</label>
                    <select name="product_type" id="product_type" required>
                        <?php foreach (getProductTypes() as $type): ?>
                            <option value="<?= $type ?>" <?= $entry['product_type'] === $type ? 'selected' : '' ?>>
                                <?= $type ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="specification">Specification:</label>
                    <select name="specification" id="specification" required>
                        <?php foreach (getSpecifications() as $spec): ?>
                            <option value="<?= $spec ?>" <?= $entry['specification'] === $spec ? 'selected' : '' ?>>
                                <?= $spec ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="ex_mill">Ex Mill Price (₹):</label>
                    <input type="number" name="ex_mill" id="ex_mill" step="0.01" value="<?= $entry['ex_mill'] ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="qty_kgs">Quantity (Kgs):</label>
                    <input type="number" name="qty_kgs" id="qty_kgs" step="0.01" value="<?= $entry['qty_kgs'] ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="local_freight">Local Freight (INR):</label>
                    <input type="number" name="local_freight" id="local_freight" step="0.01" value="<?= $entry['local_freight'] ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="other_exp">Other Expenses (INR):</label>
                    <input type="number" name="other_exp" id="other_exp" step="0.01" value="<?= $entry['other_exp'] ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="lc_terms">L/C Terms:</label>
                    <input type="number" name="lc_terms" id="lc_terms" step="0.01" value="<?= $entry['lc_terms'] ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="exchange_rate">Exchange Rate (INR/USD):</label>
                    <input type="number" name="exchange_rate" id="exchange_rate" step="0.01" value="<?= $entry['exchange_rate'] ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="margin">Margin (%):</label>
                    <input type="number" name="margin" id="margin" step="0.01" value="<?= $entry['margin'] * 100 ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="drawback">Drawback (%):</label>
                    <input type="number" name="drawback" id="drawback" step="0.01" value="<?= $entry['drawback'] * 100 ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="focus_mkt_scheme">Focus Market Scheme (%):</label>
                    <input type="number" name="focus_mkt_scheme" id="focus_mkt_scheme" step="0.01" value="<?= $entry['focus_mkt_scheme'] * 100 ?>">
                </div>
                
                <div class="form-group">
                    <label for="ocean_freight_usd">Ocean Freight (USD):</label>
                    <input type="number" name="ocean_freight_usd" id="ocean_freight_usd" step="0.01" value="<?= $entry['ocean_freight_usd'] ?>">
                </div>
                
                <div class="form-group">
                    <label for="entry_date">Entry Date:</label>
                    <input type="date" name="entry_date" id="entry_date" value="<?= date('Y-m-d', strtotime($entry['entry_date'])) ?>" required>
                </div>
                
                <button type="submit" name="update_entry">Update Entry</button>
                <a href="admin.php" class="btn">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>
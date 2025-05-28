<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

session_start();

// Get filter values from GET or use defaults
$filter_date = $_GET['entry_date'] ?? null; // Allow date to be initially null for fetching latest
$filter_type = $_GET['product_type'] ?? '6KCH'; // Default KCH
$filter_spec = $_GET['specification'] ?? '40ft'; // Default FT

$entry = null;
$display_date = $filter_date ?? date('Y-m-d'); // Date for the input field, defaults to today if no filter_date

if ($filter_date) {
    // Try to find an exact match for date, type, and spec
    $entries_for_specific_date = getExportEntries([
        'entry_date' => $filter_date,
        'product_type' => $filter_type,
        'specification' => $filter_spec
    ]);
    if (!empty($entries_for_specific_date)) {
        $entry = $entries_for_specific_date[0];
        $display_date = $entry['entry_date']; // Ensure date picker shows the actual entry's date
    }
}

if (!$entry) {
    // If no exact date match (or date wasn't provided via GET), find the most recent for KCH/FT
    $entries_for_type_spec = getExportEntries([
        'product_type' => $filter_type,
        'specification' => $filter_spec
    ]);
    if (!empty($entries_for_type_spec)) {
        $entry = $entries_for_type_spec[0]; // Get the most recent one
        $display_date = $entry['entry_date']; // Update display_date to the date of the found entry
    }
}

if ($entry) {
    $entry = calculateExportPricing($entry);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Export Pricing Viewer</title>
    <?php echo getCSS(); ?>
    <style>
        .readonly-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(67,97,238,0.08);
            padding: 2rem;
            margin-bottom: 2rem;
            max-width: 600px;
        }
        .readonly-form label {
            font-weight: 600;
            color: #3f37c9;
            margin-bottom: 0.2rem;
        }
        .readonly-form .value {
            font-weight: 500;
            color: #212529;
            background: #f8f9fa;
            border-radius: 6px;
            padding: 0.4rem 0.8rem;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px 10px;
        }
        h1 {
            color: #3f37c9;
            margin-bottom: 2rem;
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
        .filter-form {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .filter-form .form-group {
            display: flex;
            flex-direction: column;
        }
        @media (max-width: 700px) {
            .container { padding: 5px; }
            .readonly-form { grid-template-columns: 1fr; padding: 1rem; }
            .filter-form { flex-direction: column; gap: 0.5rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="admin.php" class="btn">Admin Panel</a>
            <a href="index.php" class="btn">Refresh Data</a>
        </div>
        <h1>Export Pricing Details</h1>
        <form class="filter-form" method="get" action="index.php">
            <div class="form-group">
                <label for="entry_date">Date</label>
                <input type="date" name="entry_date" id="entry_date" value="<?= htmlspecialchars($display_date) ?>" required>
            </div>
            <div class="form-group">
                <label for="product_type">Product Type</label>
                <select name="product_type" id="product_type" required>
                    <?php foreach (getProductTypes() as $type): ?>
                        <option value="<?= htmlspecialchars($type) ?>" <?= $filter_type === $type ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="specification">Specification</label>
                <select name="specification" id="specification" required>
                    <?php foreach (getSpecifications() as $spec): ?>
                        <option value="<?= htmlspecialchars($spec) ?>" <?= $filter_spec === $spec ? 'selected' : '' ?>><?= htmlspecialchars($spec) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn">Search</button>
        </form>
        <?php if ($entry): ?>
        <?php 
        // Ensure all calculated fields are present for display, recalculate if necessary.
        // This is vital if $entry comes from DB and might not have all C* fields (e.g. older data) 
        // or if you always want to show the absolute latest calculation logic.
        $display_data = calculateExportPricing($entry); 
        ?>
        <form class="readonly-form" autocomplete="off" onsubmit="return false;">
            <label>Ex Mill Price (₹)</label>
            <div class="value"><?= formatCurrency($display_data['ex_mill_price'] ?? 0) ?></div>

            <label>Comm Rate (%)</label>
            <div class="value"><?= htmlspecialchars(($display_data['comm_rate'] ?? 0) * 100) ?></div>

            <label>Commission Amount (₹)</label>
            <div class="value"><?= formatCurrency($display_data['C1_comm_value'] ?? 0) ?></div>

            <label>Qty Kgs</label>
            <div class="value"><?= htmlspecialchars(number_format((float)($display_data['qty_kgs'] ?? 0), 2)) ?></div>

            <label>Local Freight INR (Total)</label>
            <div class="value"><?= formatCurrency($display_data['local_freight_inr_total'] ?? 0) ?></div>

            <label>Local Freight (₹/Kg)</label>
            <div class="value"><?= formatCurrency($display_data['C2_local_freight_per_kg'] ?? 0) ?></div>

            <label>Other Exp (₹/Unit)</label> 
            <div class="value"><?= formatCurrency($display_data['C4_other_exp_value'] ?? 0) // This is other_exp_input ?></div>

            <label>L/C Terms (Days)</label>
            <div class="value"><?= htmlspecialchars($display_data['lc_terms_days'] ?? 'N/A') ?></div>

            <label>L/C Cost (₹)</label>
            <div class="value"><?= formatCurrency($display_data['C6_lc_terms_value'] ?? 0) ?></div>

            <label>Exchange Rate (INR/USD)</label>
            <div class="value"><?= htmlspecialchars(number_format((float)($display_data['exchange_rate'] ?? 0), 4)) ?></div>

            <label>Margin Rate (%)</label>
            <div class="value"><?= htmlspecialchars(($display_data['margin_rate'] ?? 0) * 100) ?></div>

            <label>Margin Amount (₹)</label>
            <div class="value"><?= formatCurrency($display_data['C10_margin_value'] ?? 0) ?></div>

            <label>FOB Value (₹)</label>
            <div class="value"><?= formatCurrency($display_data['C12_fob_value'] ?? 0) ?></div>

            <label>Drawback Rate (%)</label>
            <div class="value"><?= htmlspecialchars(($display_data['drawback_rate'] ?? 0) * 100) ?></div>

            <label>Drawback Amount (₹)</label>
            <div class="value"><?= formatCurrency($display_data['C13_drawback_value'] ?? 0) ?></div>

            <label>Focus Mkt Scheme Rate (%)</label>
            <div class="value"><?= htmlspecialchars(($display_data['focus_mkt_scheme_rate'] ?? 0) * 100) ?></div>

            <label>Focus Mkt Scheme Amount (₹)</label>
            <div class="value"><?= formatCurrency($display_data['C14_focus_mkt_scheme_value'] ?? 0) ?></div>

            <label>Net Price (₹/Kg)</label>
            <div class="value"><?= formatCurrency($display_data['C16_net_price_inr_kgs'] ?? 0) ?></div>

            <label>Ocean Freight (Total USD)</label>
            <div class="value"><?= formatCurrency($display_data['ocean_freight_usd_total'] ?? 0, '$') ?></div>

            <label>Ocean Freight (₹/Kg)</label>
            <div class="value"><?= formatCurrency($display_data['C17_ocean_freight_per_kg_inr'] ?? 0) ?></div>

            <label>Final Price (₹/Kg)</label>
            <div class="value"><?= formatCurrency($display_data['C18_final_price_inr'] ?? 0) ?></div>

            <label>Final Price ($/Kg - Version 1)</label>
            <div class="value"><?= formatCurrency($display_data['C19_final_price_usd_per_kg'] ?? 0, '$', 4) ?></div>

            <label>Final Price (Total $ - Version 1)</label>
            <div class="value"><?= formatCurrency($display_data['C19_final_price_usd_total'] ?? 0, '$') ?></div>

            <label>Net Price ($/Kg - Version 2 Alt)</label>
            <div class="value"><?= formatCurrency($display_data['C20_net_price_usd_alt'] ?? 0, '$', 4) ?></div>
        </form>
        <?php else: ?>
            <div class="alert alert-danger">No data found for the selected criteria. Please try different filters or contact admin if data is missing.</div>
        <?php endif; ?>
    </div>
</body>
</html>

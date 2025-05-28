<?php
require_once 'config.php';

function exportToCSV($data, $filename = 'export.csv') {
    if (empty($data)) {
        return false;
    }
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Write headers
    $first_row = $data[0];
    if (!isset($first_row['C1_comm_value'])) {
        $processed_data = [];
        foreach($data as $row_to_process){
            $processed_data[] = calculateExportPricing($row_to_process);
        }
        $data = $processed_data;
        $first_row = $data[0];
    }
    fputcsv($output, array_keys($first_row));
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

function calculateExportPricing($inputs) {
    // Ensure all expected inputs are present and cast to float, defaulting to 0 if not set.
    $ex_mill_price = (float)($inputs['ex_mill_price'] ?? 0);
    $comm_rate = (float)($inputs['comm_rate'] ?? 0); // decimal e.g., 0.01 for 1%
    $qty_kgs = (float)($inputs['qty_kgs'] ?? 0);
    $local_freight_inr_total = (float)($inputs['local_freight_inr_total'] ?? 0);
    $other_exp_input = (float)($inputs['other_exp_input'] ?? 0); // 'other_exp_input' is assumed to be the 'Other Expenses Amount' per unit
    $lc_terms_days = (float)($inputs['lc_terms_days'] ?? 0);
    $exchange_rate = (float)($inputs['exchange_rate'] ?? 0);
    $margin_rate = (float)($inputs['margin_rate'] ?? 0); // decimal
    $drawback_rate = (float)($inputs['drawback_rate'] ?? 0); // decimal
    $focus_mkt_scheme_rate = (float)($inputs['focus_mkt_scheme_rate'] ?? 0); // decimal
    $ocean_freight_usd_total = (float)($inputs['ocean_freight_usd_total'] ?? 0);

    // Calculations as per user's list
    $commission_amount = $ex_mill_price * $comm_rate;
    $local_freight_per_kg = ($qty_kgs > 0) ? $local_freight_inr_total / $qty_kgs : 0;
    $other_exp_value = $other_exp_input; // Direct value
    $lc_cost = ($ex_mill_price * 0.09 * $lc_terms_days) / 365;
    $margin_amount = $ex_mill_price * $margin_rate;

    $fob_value_calc = $ex_mill_price + $commission_amount + $local_freight_per_kg + $other_exp_value + $lc_cost + $margin_amount;
    
    $drawback_amount = $fob_value_calc * $drawback_rate;
    
    // FMS: (Ex Mill Price - Commission Amount - Local Freight/kg) * FMS Rate * 0.9
    $fms_base = $ex_mill_price - $commission_amount - $local_freight_per_kg;
    $fms_amount = ($fms_base > 0) ? ($fms_base * $focus_mkt_scheme_rate * 0.9) : 0;

    // Net Price: FOB - Drawback + FMS (adding FMS incentive)
    $net_price_inr_kgs_calc = $fob_value_calc - $drawback_amount + $fms_amount;
    
    $ocean_freight_per_kg_inr_calc = 0;
    if ($qty_kgs > 0 && $exchange_rate > 0) {
        $ocean_freight_per_kg_inr_calc = ($ocean_freight_usd_total * $exchange_rate) / $qty_kgs;
    }

    // Final Price (INR per Kg)
    $final_price_inr_per_kg_calc = $net_price_inr_kgs_calc + $ocean_freight_per_kg_inr_calc;
    
    // Net Price in USD (per Kg, Version 1)
    $final_price_usd_per_kg_calc = 0;
    if ($exchange_rate > 0) {
        $final_price_usd_per_kg_calc = $final_price_inr_per_kg_calc / $exchange_rate;
    }

    // Final Price (Total USD)
    $final_price_usd_total_calc = 0;
    if ($qty_kgs > 0) { // Ensure qty_kgs is not zero
      $final_price_usd_total_calc = $final_price_usd_per_kg_calc * $qty_kgs;
    }


    // Net Price in USD (Alt Version 2)
    $net_price_usd_alt_calc = 0;
    if ($margin_rate > 0 && $final_price_usd_per_kg_calc > 0) { // Avoid division by zero
        $net_price_usd_alt_calc = $final_price_usd_per_kg_calc / $margin_rate;
    }
    
    $calculated_data = [
        // Original Inputs (passed through)
        'ex_mill_price' => $ex_mill_price,
        'comm_rate' => $comm_rate,
        'qty_kgs' => $qty_kgs,
        'local_freight_inr_total' => $local_freight_inr_total,
        'other_exp_input' => $other_exp_input, // This is C4_other_exp_value
        'lc_terms_days' => $lc_terms_days,
        'exchange_rate' => $exchange_rate,
        'margin_rate' => $margin_rate,
        'drawback_rate' => $drawback_rate,
        'focus_mkt_scheme_rate' => $focus_mkt_scheme_rate,
        'ocean_freight_usd_total' => $ocean_freight_usd_total,
        // Pass through non-calculation fields like date, type, spec, id
        'entry_date' => $inputs['entry_date'] ?? null,
        'product_type' => $inputs['product_type'] ?? null,
        'specification' => $inputs['specification'] ?? null,
        'id' => $inputs['id'] ?? null,

        // Calculated Values with C*_ prefixes
        'C1_comm_value' => $commission_amount,
        'C2_local_freight_per_kg' => $local_freight_per_kg,
        'C4_other_exp_value' => $other_exp_value, // Note: same as other_exp_input
        'C6_lc_terms_value' => $lc_cost,
        'C10_margin_value' => $margin_amount,
        'C12_fob_value' => $fob_value_calc,
        'C13_drawback_value' => $drawback_amount,
        'C14_focus_mkt_scheme_value' => $fms_amount,
        'C16_net_price_inr_kgs' => $net_price_inr_kgs_calc,
        'C17_ocean_freight_per_kg_inr' => $ocean_freight_per_kg_inr_calc,
        'C18_final_price_inr' => $final_price_inr_per_kg_calc, // This is per KG
        'C19_final_price_usd_per_kg' => $final_price_usd_per_kg_calc,
        'C19_final_price_usd_total' => $final_price_usd_total_calc, //This is total USD
        'C20_net_price_usd_alt' => $net_price_usd_alt_calc
    ];
    
    // Merge to ensure any other fields from $inputs (like id, entry_date etc.) are preserved
    return array_merge($inputs, $calculated_data); 
}

function saveExportEntry($data) {
    $db = getDB();
    $all_data_to_save = calculateExportPricing($data);

    $db_columns = [
        'entry_date', 'product_type', 'specification',
        'ex_mill_price', 'comm_rate', 'qty_kgs', 'local_freight_inr_total', 'other_exp_input', 
        'lc_terms_days', 'exchange_rate', 'margin_rate', 'drawback_rate', 
        'focus_mkt_scheme_rate', 'ocean_freight_usd_total',
        'C1_comm_value', 'C2_local_freight_per_kg', 'C4_other_exp_value', 'C6_lc_terms_value',
        'C10_margin_value', 'C12_fob_value', 'C13_drawback_value', 'C14_focus_mkt_scheme_value',
        'C16_net_price_inr_kgs', 'C17_ocean_freight_per_kg_inr', 'C18_final_price_inr', 
        'C19_final_price_usd_per_kg', 'C19_final_price_usd_total', 'C20_net_price_usd_alt'
    ];

    $columns_sql = implode(", ", $db_columns);
    $placeholders_sql = ":" . implode(", :", $db_columns);
    $sql = "INSERT INTO export_entries ($columns_sql) VALUES ($placeholders_sql)";
    
    $stmt = $db->prepare($sql);

    $params_for_sql = [];
    foreach ($db_columns as $column_name) {
        $params_for_sql[$column_name] = $all_data_to_save[$column_name] ?? null;
    }

    try {
        $success = $stmt->execute($params_for_sql);
        if (!$success) {
            // Optional: Capture more detailed error info from statement if execute returns false but doesn't throw PDOException
            $errorInfo = $stmt->errorInfo();
            $_SESSION['pdo_error'] = "SQL Error: " . ($errorInfo[2] ?? 'Unknown error from execute()');
            error_log("Error saving export entry (execute returned false): " . ($errorInfo[2] ?? 'Unknown error') . " SQL: " . $sql . " Params: " . json_encode($params_for_sql));
        }
        return $success;
    } catch (PDOException $e) {
        $_SESSION['pdo_error'] = "DB Exception: " . $e->getMessage(); // Store specific error for admin page
        error_log("Error saving export entry (PDOException): " . $e->getMessage() . " SQL: " . $sql . " Params: " . json_encode($params_for_sql));
        return false;
    }
}

function getExportEntries($filter = []) {
    $db = getDB();
    $where = [];
    $params = [];
    
    if (!empty($filter['product_type'])) {
        $where[] = "product_type = :product_type";
        $params[':product_type'] = $filter['product_type'];
    }
    
    if (!empty($filter['specification'])) {
        $where[] = "specification = :specification";
        $params[':specification'] = $filter['specification'];
    }
    
    if (!empty($filter['entry_date'])) {
        $where[] = "entry_date = :entry_date";
        $params[':entry_date'] = $filter['entry_date'];
    }
    
    if (!empty($filter['from_date']) && !empty($filter['to_date'])) {
        $where[] = "entry_date BETWEEN :from_date AND :to_date";
        $params[':from_date'] = $filter['from_date'];
        $params[':to_date'] = $filter['to_date'];
    }
    
    $sql = "SELECT * FROM export_entries";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY entry_date DESC, id DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // It's good practice to ensure all entries have all calculated fields,
    // especially if some older entries in DB might not have them.
    // index.php and admin.php already do this after fetching, so this might be redundant here
    // but can be useful if getExportEntries is used elsewhere directly.
    /*
    $processed_results = [];
    foreach ($results as $row) {
        $processed_results[] = calculateExportPricing($row);
    }
    return $processed_results;
    */
    return $results; // Return raw results; calculation is handled by admin.php/index.php display logic
}

function getProductTypes() {
    $db = getDB();
    $stmt = $db->query("SELECT DISTINCT product_type FROM export_entries ORDER BY product_type ASC");
    $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $default_types = ['6KCH', '9KCH']; 
    return array_unique(array_merge($default_types, $types));
}

function getSpecifications() {
    $db = getDB();
    $stmt = $db->query("SELECT DISTINCT specification FROM export_entries ORDER BY specification ASC");
    $specs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $default_specs = ['40ft', '20ft'];
    return array_unique(array_merge($default_specs, $specs));
}

function formatCurrency($value, $currency = '₹') {
    if ($value === null || $value === '') return $currency . '0.00'; // Handle null or empty
    return $currency . number_format((float)$value, 2, '.', ',');
}

function formatDate($date) {
    if (empty($date) || $date === '0000-00-00') return 'N/A';
    return date('d M Y', strtotime($date));
}

// Helper for admin password (ensure it's defined if not already)
if (!function_exists('verifyAdminPassword')) {
    function verifyAdminPassword($password_attempt) {
        // For proper security, ADMIN_PASSWORD_HASH should be defined in your config.php
        // Example: define('ADMIN_PASSWORD_HASH', password_hash("your_secure_password", PASSWORD_DEFAULT));
        return defined('ADMIN_PASSWORD_HASH') ? password_verify($password_attempt, ADMIN_PASSWORD_HASH) : ($password_attempt === 'admin123'); // Fallback for undefined constant
    }
}

// Helper for redirect (ensure it's defined if not already)
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: " . $url);
        exit;
    }
}

// Helper for isAdmin (ensure it's defined if not already)
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }
}

?>
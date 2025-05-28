<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    if (!file_exists('costt.xlsx')) {
        throw new Exception("Excel file 'costt.xlsx' not found!");
    }

    $spreadsheet = IOFactory::load('costt.xlsx');
    $sheet = $spreadsheet->getActiveSheet();
    $successCount = 0;
    $errorCount = 0;

    foreach ($sheet->getRowIterator() as $row) {
        $rowIndex = $row->getRowIndex();

        // Skip header row
        if ($rowIndex == 1) continue;

        try {
            $entryDate = $sheet->getCell('A' . $rowIndex)->getValue();
            if (is_numeric($entryDate)) {
                $entryDate = Date::excelToDateTimeObject($entryDate)->format('Y-m-d');
            }

            $data = [
                'entry_date' => $entryDate,
                'product_type' => trim($sheet->getCell('B' . $rowIndex)->getValue()),
                'specification' => trim($sheet->getCell('C' . $rowIndex)->getValue()),
                'ex_mill' => (float)$sheet->getCell('D' . $rowIndex)->getValue(),
                'qty_kgs' => (float)$sheet->getCell('E' . $rowIndex)->getValue(),
                'local_freight' => (float)$sheet->getCell('F' . $rowIndex)->getValue(),
                'other_exp' => (float)$sheet->getCell('G' . $rowIndex)->getValue(),
                'lc_terms' => (float)$sheet->getCell('H' . $rowIndex)->getValue(),
                'exchange_rate' => (float)$sheet->getCell('I' . $rowIndex)->getValue(),
                'margin' => (float)$sheet->getCell('J' . $rowIndex)->getValue(),
                'drawback' => (float)$sheet->getCell('K' . $rowIndex)->getValue(),
                'focus_mkt_scheme' => (float)$sheet->getCell('L' . $rowIndex)->getValue(),
                'ocean_freight_usd' => (float)$sheet->getCell('M' . $rowIndex)->getValue(),
            ];

            // Validate required fields
            $requiredFields = ['entry_date', 'product_type', 'specification', 'ex_mill', 'qty_kgs'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                    throw new Exception("Row {$rowIndex}: Required field '{$field}' is empty");
                }
            }

            // Validate numeric fields
            $numericFields = ['ex_mill', 'qty_kgs', 'local_freight', 'other_exp', 'exchange_rate', 'margin'];
            foreach ($numericFields as $field) {
                if (!is_numeric($data[$field])) {
                    throw new Exception("Row {$rowIndex}: Field '{$field}' must be numeric");
                }
            }

            if (saveExportEntry($data)) {
                $successCount++;
            } else {
                throw new Exception("Failed to save data for row {$rowIndex}");
            }

        } catch (Exception $e) {
            error_log("Error processing row {$rowIndex}: " . $e->getMessage(), 3, "import_errors.log");
            $errorCount++;
        }
    }

    echo "Import completed: {$successCount} rows imported successfully, {$errorCount} rows failed.";
    if ($errorCount > 0) {
        echo "<br>Check 'import_errors.log' for details.";
    }

} catch (Exception $e) {
    error_log("Excel import error: " . $e->getMessage(), 3, "import_errors.log");
    echo "Error: " . $e->getMessage();
}


/**
 * Save export entry to the database
 */
function saveExportEntry($data) {
    $db = getDB();
    $calculated = calculateExportPricing($data);

    $stmt = $db->prepare("INSERT INTO export_entries (
        product_type, specification, ex_mill, qty_kgs, local_freight, other_exp,
        lc_terms, exchange_rate, margin, drawback, focus_mkt_scheme, ocean_freight_usd,
        entry_date, comm, local_freight_per_kg, lc_terms_value, margin_value,
        fob_value, drawback_value, focus_mkt_scheme_value, net_price_inr,
        ocean_freight_per_kg, final_price_inr, final_price_usd
    ) VALUES (
        :product_type, :specification, :ex_mill, :qty_kgs, :local_freight, :other_exp,
        :lc_terms, :exchange_rate, :margin, :drawback, :focus_mkt_scheme, :ocean_freight_usd,
        :entry_date, :comm, :local_freight_per_kg, :lc_terms_value, :margin_value,
        :fob_value, :drawback_value, :focus_mkt_scheme_value, :net_price_inr,
        :ocean_freight_per_kg, :final_price_inr, :final_price_usd
    )");

    $params = array_merge($data, $calculated);

    if (!$stmt->execute($params)) {
        error_log("DB Error: " . implode(" | ", $stmt->errorInfo()), 3, "import_errors.log");
        return false;
    }

    return true;
}


/**
 * Dummy pricing calculation — replace with real logic
 */
function calculateExportPricing($data) {
    $qty = $data['qty_kgs'] > 0 ? $data['qty_kgs'] : 1;

    $net_price_inr = $data['ex_mill'] + $data['local_freight'] + $data['other_exp'];
    $final_price_inr = $net_price_inr - $data['margin'];
    $final_price_usd = ($data['exchange_rate'] > 0) ? ($final_price_inr / $data['exchange_rate']) : 0;

    return [
        'comm' => 0,
        'local_freight_per_kg' => $data['local_freight'] / $qty,
        'lc_terms_value' => $data['lc_terms'],
        'margin_value' => $data['margin'],
        'fob_value' => $data['ex_mill'],
        'drawback_value' => $data['drawback'],
        'focus_mkt_scheme_value' => $data['focus_mkt_scheme'],
        'net_price_inr' => $net_price_inr,
        'ocean_freight_per_kg' => $data['ocean_freight_usd'] / $qty,
        'final_price_inr' => $final_price_inr,
        'final_price_usd' => $final_price_usd
    ];
}
?>

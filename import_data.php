<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$db = getDB();

// Sample data for 6KCH and 40ft (you'll need to replace these values with your Excel data)
$initial_data = [
    [
        'product_type' => '6KCH',
        'specification' => '40ft',
        'ex_mill' => 150.00,
        'qty_kgs' => 20412.00,
        'local_freight' => 75000.00,
        'other_exp' => 1.00,
        'lc_terms' => 90.00,
        'exchange_rate' => 84.50,
        'margin' => 0.01, // 1%
        'drawback' => 0.02, // 2%
        'focus_mkt_scheme' => 0.00, // 0%
        'ocean_freight_usd' => 0.00,
        'entry_date' => date('Y-m-d')
    ]
];

try {
    foreach ($initial_data as $data) {
        // Calculate all the derived values using the existing function
        $result = saveExportEntry($data);
        
        if ($result) {
            echo "Successfully imported data for {$data['product_type']} {$data['specification']}\n";
        } else {
            echo "Failed to import data for {$data['product_type']} {$data['specification']}\n";
        }
    }
    
    echo "\nData import completed!\n";
    echo "You can now view the data on the frontend at index.php\n";
    echo "To add more entries or edit existing ones, use the Admin Panel.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 
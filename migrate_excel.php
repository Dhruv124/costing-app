<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$db = getDB();

// Sample data from Excel (you'll need to parse your actual Excel file)
$excelData = [
    [
        'entry_date' => '2025-05-05',
        'product_type' => '6KCH',
        'specification' => '40ft',
        'ex_mill' => 150,
        'qty_kgs' => 20412,
        'local_freight' => 75000,
        'other_exp' => 1,
        'lc_terms' => 90,
        'exchange_rate' => 84.5,
        'margin' => 0.01,
        'drawback' => 0.02,
        'focus_mkt_scheme' => 0,
        'ocean_freight_usd' => 0 // This needs to be calculated or extracted from Excel
    ],
    [
        'entry_date' => '2025-05-05',
        'product_type' => '9KCH',
        'specification' => '40ft',
        'ex_mill' => 180,
        'qty_kgs' => 22000,
        'local_freight' => 75220,
        'other_exp' => 1,
        'lc_terms' => 99,
        'exchange_rate' => 88,
        'margin' => 0.01,
        'drawback' => 0.03,
        'focus_mkt_scheme' => 0,
        'ocean_freight_usd' => 0 // This needs to be calculated or extracted from Excel
    ]
];

foreach ($excelData as $data) {
    saveExportEntry($data);
}

echo "Data migrated successfully!";
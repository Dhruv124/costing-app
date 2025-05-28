<?php
require_once 'includes/config.php';

$db = getDB();

$tableName = 'export_entries';

try {
    // Step 1: Check if the table exists
    $stmt = $db->prepare("SHOW TABLES LIKE :table");
    $stmt->execute([':table' => $tableName]);

    if ($stmt->rowCount() === 0) {
        // Table does not exist, create it
        $createTableSQL = "
            CREATE TABLE $tableName (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_type ENUM('6KCH', '9KCH') NOT NULL,
                specification VARCHAR(50) NOT NULL,
                ex_mill DECIMAL(12,2) NOT NULL,
                qty_kgs DECIMAL(12,2) NOT NULL,
                local_freight DECIMAL(12,2) NOT NULL,
                other_exp DECIMAL(12,2) NOT NULL,
                lc_terms DECIMAL(12,2) NOT NULL,
                exchange_rate DECIMAL(12,2) NOT NULL,
                margin DECIMAL(5,2) NOT NULL,
                drawback DECIMAL(5,2) NOT NULL,
                focus_mkt_scheme DECIMAL(12,2) NOT NULL DEFAULT 0,
                ocean_freight_usd DECIMAL(12,2) NOT NULL DEFAULT 0,
                entry_date DATE NOT NULL,
                comm DECIMAL(12,2),
                local_freight_per_kg DECIMAL(12,2),
                lc_terms_value DECIMAL(12,2),
                margin_value DECIMAL(12,2),
                fob_value DECIMAL(12,2),
                drawback_value DECIMAL(12,2),
                focus_mkt_scheme_value DECIMAL(12,2),
                net_price_inr DECIMAL(12,2),
                ocean_freight_per_kg DECIMAL(12,2),
                final_price_inr DECIMAL(12,2),
                final_price_usd DECIMAL(12,2),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_entry (entry_date, product_type, specification)
            )
        ";
        $db->exec($createTableSQL);
        echo "✅ Table '$tableName' created successfully.\n";
    } else {
        echo "ℹ️ Table '$tableName' already exists.\n";

        // Step 2: Add missing columns
        $columnsToAdd = [
            'comm' => 'DECIMAL(12,2)',
            'local_freight_per_kg' => 'DECIMAL(12,2)',
            'lc_terms_value' => 'DECIMAL(12,2)',
            'margin_value' => 'DECIMAL(12,2)',
            'fob_value' => 'DECIMAL(12,2)',
            'drawback_value' => 'DECIMAL(12,2)',
            'focus_mkt_scheme_value' => 'DECIMAL(12,2)',
            'net_price_inr' => 'DECIMAL(12,2)',
            'ocean_freight_per_kg' => 'DECIMAL(12,2)',
            'final_price_inr' => 'DECIMAL(12,2)',
            'final_price_usd' => 'DECIMAL(12,2)'
        ];

        foreach ($columnsToAdd as $column => $type) {
            // Check if column exists
            $check = $db->prepare("
                SELECT COUNT(*) FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column
            ");
            $check->execute([':table' => $tableName, ':column' => $column]);
            $exists = $check->fetchColumn();

            if (!$exists) {
                $alter = "ALTER TABLE $tableName ADD COLUMN $column $type";
                $db->exec($alter);
                echo "✅ Added column: $column\n";
            } else {
                echo "✔️ Column already exists: $column\n";
            }
        }

        echo "\n✅ Schema verified and updated successfully.\n";
    }

    echo "\n💡 You can now run import_data.php to load data.\n";

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>

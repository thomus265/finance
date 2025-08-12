<?php
/**
 * Migration script to update existing transactions with unique transaction IDs
 * Run this script once to update existing data
 */

require('../controller/main.php');

echo "Starting migration to add unique transaction IDs...\n";

try {
    // First, ensure we have a valid user for foreign key constraints
    $existing_user = $db->query("SELECT user_id FROM users WHERE user_id = 1")->fetchArray();
    if (!$existing_user) {
        echo "Adding default test user (user_id = 1)...\n";
        $db->query("
            INSERT INTO users (user_id, user_name, user_email, user_type, email_verified, verified_at, created_at, updated_at)
            VALUES (1, 'Test User', 'test.user@undp.org', 3, 1, NOW(), NOW(), NOW())
        ");
        echo "Default user added successfully.\n";
    }
    // First, check if the transaction_id column exists
    $check_column = $db->query("SHOW COLUMNS FROM transactions LIKE 'transaction_id'")->fetchArray();
    
    if (!$check_column) {
        echo "Adding transaction_id column to transactions table...\n";
        $db->query("ALTER TABLE transactions ADD COLUMN transaction_id varchar(20) AFTER id");
        echo "Column added successfully.\n";
    }

    // Get all transactions that don't have a transaction_id
    $transactions = $db->query("
        SELECT id, tr_date 
        FROM transactions 
        WHERE transaction_id IS NULL OR transaction_id = ''
        ORDER BY tr_date ASC, id ASC
    ")->fetchAll();

    if (empty($transactions)) {
        echo "No transactions need updating.\n";
        exit;
    }

    echo "Found " . count($transactions) . " transactions to update.\n";

    $updated_count = 0;
    $year_counters = [];

    foreach ($transactions as $trans) {
        $year = date('Y', strtotime($trans['tr_date']));
        
        // Initialize counter for this year if not exists
        if (!isset($year_counters[$year])) {
            // Get the highest existing transaction ID for this year
            $existing = $db->query("
                SELECT transaction_id 
                FROM transactions 
                WHERE transaction_id LIKE ? 
                AND transaction_id IS NOT NULL 
                AND transaction_id != ''
                ORDER BY transaction_id DESC 
                LIMIT 1
            ", "TXN-{$year}-%")->fetchArray();
            
            if ($existing && $existing['transaction_id']) {
                // Extract the number part and start from the next number
                $last_number = intval(substr($existing['transaction_id'], -6));
                $year_counters[$year] = $last_number + 1;
            } else {
                $year_counters[$year] = 1;
            }
        }

        // Generate unique transaction ID
        $transaction_id = "TXN-{$year}-" . str_pad($year_counters[$year], 6, '0', STR_PAD_LEFT);
        
        // Update the transaction
        $result = $db->query(
            "UPDATE transactions SET transaction_id = ? WHERE id = ?",
            $transaction_id, $trans['id']
        );
        
        if ($result) {
            $updated_count++;
            echo "Updated transaction ID {$trans['id']} -> {$transaction_id}\n";
        } else {
            echo "Failed to update transaction ID {$trans['id']}\n";
        }
        
        $year_counters[$year]++;
    }

    // Add unique index if it doesn't exist
    try {
        $db->query("ALTER TABLE transactions ADD UNIQUE KEY unique_transaction_id (transaction_id)");
        echo "Added unique index for transaction_id.\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "Unique index already exists.\n";
        } else {
            echo "Warning: Could not add unique index: " . $e->getMessage() . "\n";
        }
    }

    echo "\nMigration completed successfully!\n";
    echo "Updated {$updated_count} transactions.\n";

} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nMigration finished. You can now use the new transaction features.\n";
?>

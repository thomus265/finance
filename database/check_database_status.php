<?php
/**
 * Database Status Checker
 * 
 * Checks the current state of the database and identifies missing tables or issues
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Status Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; }
        .table-status { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .table-exists { background-color: #d4edda; border: 1px solid #c3e6cb; }
        .table-missing { background-color: #f8d7da; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3>Database Status Check</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            require_once('../controller/Database.php');
                            $db = new Database();
                            
                            echo "<p class='success'>✅ Database connection successful</p>";
                            
                            // Get all tables in the database
                            $tables = $db->query("SHOW TABLES")->fetchAll();
                            $existingTables = array_map(function($table) {
                                return array_values($table)[0];
                            }, $tables);
                            
                            echo "<h4>Database Tables Status</h4>";
                            
                            // Define required tables and their purposes
                            $requiredTables = [
                                'users' => 'User accounts and authentication',
                                'user_roles' => 'User role definitions',
                                'transactions' => 'Main transaction records',
                                'transaction_attachments' => 'File attachments for transactions',
                                'transaction_state_history' => 'Transaction status change history',
                                'workflow_states' => 'Available transaction states',
                                'notifications' => 'System notifications',
                                'email_log' => 'Email notification tracking (MISSING - causing current error)',
                                'departments' => 'Department information',
                                'sessions' => 'User session management'
                            ];
                            
                            $missingTables = [];
                            $existingRequiredTables = [];
                            
                            foreach ($requiredTables as $table => $description) {
                                if (in_array($table, $existingTables)) {
                                    echo "<div class='table-status table-exists'>";
                                    echo "<strong>✅ $table</strong> - $description";
                                    
                                    // Get row count
                                    try {
                                        $count = $db->query("SELECT COUNT(*) as count FROM `$table`")->fetchArray()['count'];
                                        echo " <small class='text-muted'>($count records)</small>";
                                    } catch (Exception $e) {
                                        echo " <small class='text-muted'>(count unavailable)</small>";
                                    }
                                    
                                    echo "</div>";
                                    $existingRequiredTables[] = $table;
                                } else {
                                    echo "<div class='table-status table-missing'>";
                                    echo "<strong>❌ $table</strong> - $description";
                                    echo "</div>";
                                    $missingTables[] = $table;
                                }
                            }
                            
                            // Show additional tables that exist but aren't in our required list
                            $additionalTables = array_diff($existingTables, array_keys($requiredTables));
                            if (!empty($additionalTables)) {
                                echo "<h5 class='mt-4'>Additional Tables Found</h5>";
                                foreach ($additionalTables as $table) {
                                    echo "<div class='table-status' style='background-color: #e2e3e5; border: 1px solid #d6d8db;'>";
                                    echo "<strong>ℹ️ $table</strong> - Additional table";
                                    echo "</div>";
                                }
                            }
                            
                            // Summary and actions
                            echo "<h4 class='mt-4'>Summary</h4>";
                            echo "<p><strong>Total tables found:</strong> " . count($existingTables) . "</p>";
                            echo "<p><strong>Required tables present:</strong> " . count($existingRequiredTables) . "/" . count($requiredTables) . "</p>";
                            
                            if (empty($missingTables)) {
                                echo "<div class='alert alert-success'>";
                                echo "<h5>✅ All Required Tables Present</h5>";
                                echo "<p>Your database appears to be complete. All required tables are present.</p>";
                                echo "</div>";
                            } else {
                                echo "<div class='alert alert-danger'>";
                                echo "<h5>❌ Missing Tables Detected</h5>";
                                echo "<p>The following tables are missing and may cause errors:</p>";
                                echo "<ul>";
                                foreach ($missingTables as $table) {
                                    echo "<li><strong>$table</strong> - {$requiredTables[$table]}</li>";
                                }
                                echo "</ul>";
                                echo "</div>";
                                
                                // Specific fix for email_log
                                if (in_array('email_log', $missingTables)) {
                                    echo "<div class='alert alert-warning'>";
                                    echo "<h5>🔧 Quick Fix Available</h5>";
                                    echo "<p>The <code>email_log</code> table is missing, which is causing the current error. You can fix this immediately:</p>";
                                    echo "<p><a href='create_email_log_table.php' class='btn btn-warning'>Create email_log Table Now</a></p>";
                                    echo "</div>";
                                }
                            }
                            
                            // Migration options
                            echo "<h4 class='mt-4'>Available Migrations</h4>";
                            echo "<div class='row'>";
                            
                            $migrationFiles = [
                                'create_email_log_table.php' => 'Create email_log table (fixes current error)',
                                'run_email_log_migration.php' => 'Full email_log migration with testing',
                                'run_migration.php' => 'Transaction ID migration',
                            ];
                            
                            foreach ($migrationFiles as $file => $description) {
                                if (file_exists($file)) {
                                    echo "<div class='col-md-4 mb-3'>";
                                    echo "<div class='card'>";
                                    echo "<div class='card-body'>";
                                    echo "<h6 class='card-title'>$file</h6>";
                                    echo "<p class='card-text small'>$description</p>";
                                    echo "<a href='$file' class='btn btn-sm btn-primary'>Run Migration</a>";
                                    echo "</div>";
                                    echo "</div>";
                                    echo "</div>";
                                }
                            }
                            
                            echo "</div>";
                            
                            // Database info
                            echo "<h4 class='mt-4'>Database Information</h4>";
                            $dbInfo = $db->query("SELECT DATABASE() as db_name, VERSION() as version")->fetchArray();
                            echo "<p><strong>Database:</strong> {$dbInfo['db_name']}</p>";
                            echo "<p><strong>MySQL Version:</strong> {$dbInfo['version']}</p>";
                            
                        } catch (Exception $e) {
                            echo "<div class='alert alert-danger'>";
                            echo "<h5>❌ Database Connection Failed</h5>";
                            echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                            echo "<p>Please check:</p>";
                            echo "<ul>";
                            echo "<li>MySQL service is running in XAMPP</li>";
                            echo "<li>Database credentials in controller/Database.php</li>";
                            echo "<li>The 'finance' database exists</li>";
                            echo "</ul>";
                            echo "</div>";
                        }
                        ?>
                        
                        <div class="mt-4">
                            <a href="../finance/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                            <a href="../test_database_connection.php" class="btn btn-info">Test Database Connection</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

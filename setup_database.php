<?php
/**
 * Database Setup Script
 * 
 * This script helps set up the database for the Finance Transaction System
 */

echo "<h1>Database Setup for Finance Transaction System</h1>";
echo "<style>
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.info { color: blue; }
.code { background: #f5f5f5; padding: 10px; border-radius: 3px; font-family: monospace; margin: 10px 0; }
.step { background: #f9f9f9; padding: 15px; border-left: 4px solid #007bff; margin: 10px 0; }
</style>";

// Step 1: Check MySQL connection
echo "<div class='step'>";
echo "<h2>Step 1: Check MySQL Server Connection</h2>";
check_mysql_connection();
echo "</div>";

// Step 2: Create database if it doesn't exist
echo "<div class='step'>";
echo "<h2>Step 2: Create Finance Database</h2>";
create_database();
echo "</div>";

// Step 3: Import schema
echo "<div class='step'>";
echo "<h2>Step 3: Import Database Schema</h2>";
import_schema();
echo "</div>";

// Step 4: Test Database class
echo "<div class='step'>";
echo "<h2>Step 4: Test Database Class</h2>";
test_database_class();
echo "</div>";

function check_mysql_connection() {
    echo "<p class='info'>Testing MySQL server connection...</p>";
    
    // Try to connect to MySQL server (without specific database)
    $connection = @new mysqli('localhost', 'root', '');
    
    if ($connection->connect_error) {
        echo "<p class='error'>❌ Cannot connect to MySQL server</p>";
        echo "<p class='error'>Error: " . $connection->connect_error . "</p>";
        
        echo "<div class='code'>";
        echo "<strong>Troubleshooting Steps:</strong><br>";
        echo "1. Make sure XAMPP is installed and running<br>";
        echo "2. Start MySQL service in XAMPP Control Panel<br>";
        echo "3. Check if MySQL is running on port 3306<br>";
        echo "4. Try running XAMPP as administrator<br>";
        echo "5. Check Windows Firewall settings<br>";
        echo "</div>";
        
        return false;
    }
    
    echo "<p class='success'>✅ MySQL server is running</p>";
    echo "<p class='info'>MySQL version: " . $connection->server_info . "</p>";
    
    $connection->close();
    return true;
}

function create_database() {
    echo "<p class='info'>Checking if 'finance' database exists...</p>";
    
    $connection = @new mysqli('localhost', 'root', '');
    
    if ($connection->connect_error) {
        echo "<p class='error'>❌ Cannot connect to MySQL server</p>";
        return false;
    }
    
    // Check if database exists
    $result = $connection->query("SHOW DATABASES LIKE 'finance'");
    
    if ($result && $result->num_rows > 0) {
        echo "<p class='success'>✅ 'finance' database already exists</p>";
    } else {
        echo "<p class='warning'>⚠️ 'finance' database does not exist. Creating...</p>";
        
        if ($connection->query("CREATE DATABASE finance CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
            echo "<p class='success'>✅ 'finance' database created successfully</p>";
        } else {
            echo "<p class='error'>❌ Failed to create 'finance' database</p>";
            echo "<p class='error'>Error: " . $connection->error . "</p>";
            $connection->close();
            return false;
        }
    }
    
    $connection->close();
    return true;
}

function import_schema() {
    echo "<p class='info'>Looking for database schema files...</p>";
    
    $schema_files = [
        'database/finance (2).sql',
        'database/finance.sql',
        'database/migration_workflow_system.sql',
        'database/schema.sql'
    ];
    
    $found_schema = false;
    
    foreach ($schema_files as $file) {
        if (file_exists($file)) {
            echo "<p class='success'>✅ Found schema file: $file</p>";
            $found_schema = true;
            
            echo "<p class='info'>Importing schema from $file...</p>";
            
            if (import_sql_file($file)) {
                echo "<p class='success'>✅ Schema imported successfully from $file</p>";
                break;
            } else {
                echo "<p class='error'>❌ Failed to import schema from $file</p>";
            }
        }
    }
    
    if (!$found_schema) {
        echo "<p class='warning'>⚠️ No schema files found</p>";
        echo "<div class='code'>";
        echo "<strong>Manual Setup Required:</strong><br>";
        echo "1. Open phpMyAdmin (http://localhost/phpmyadmin)<br>";
        echo "2. Select the 'finance' database<br>";
        echo "3. Import your SQL schema file<br>";
        echo "4. Or create tables manually<br>";
        echo "</div>";
    }
}

function import_sql_file($file) {
    try {
        $connection = new mysqli('localhost', 'root', '', 'finance');
        
        if ($connection->connect_error) {
            echo "<p class='error'>Cannot connect to finance database: " . $connection->connect_error . "</p>";
            return false;
        }
        
        $sql = file_get_contents($file);
        
        if ($sql === false) {
            echo "<p class='error'>Cannot read file: $file</p>";
            return false;
        }
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                if ($connection->query($statement)) {
                    $success_count++;
                } else {
                    $error_count++;
                    echo "<p class='warning'>⚠️ SQL Error: " . $connection->error . "</p>";
                }
            }
        }
        
        echo "<p class='info'>Executed $success_count statements successfully, $error_count errors</p>";
        
        $connection->close();
        return $error_count == 0;
        
    } catch (Exception $e) {
        echo "<p class='error'>Exception importing SQL: " . $e->getMessage() . "</p>";
        return false;
    }
}

function test_database_class() {
    echo "<p class='info'>Testing Database class...</p>";
    
    try {
        if (!file_exists('controller/Database.php')) {
            echo "<p class='error'>❌ Database.php file not found</p>";
            return false;
        }
        
        require_once('controller/Database.php');
        
        echo "<p class='info'>Creating Database instance...</p>";
        $db = new Database();
        
        echo "<p class='success'>✅ Database connection successful!</p>";
        
        // Test a simple query
        $result = $db->query("SELECT 1 as test")->fetchArray();
        if ($result && $result['test'] == 1) {
            echo "<p class='success'>✅ Database query test successful!</p>";
        }
        
        // Check for tables
        $tables = $db->query("SHOW TABLES")->fetchAll();
        if ($tables && count($tables) > 0) {
            echo "<p class='success'>✅ Database contains " . count($tables) . " tables</p>";
            echo "<p class='info'>Tables found:</p>";
            echo "<ul>";
            foreach ($tables as $table) {
                $table_name = array_values($table)[0];
                echo "<li>$table_name</li>";
            }
            echo "</ul>";
        } else {
            echo "<p class='warning'>⚠️ Database exists but contains no tables</p>";
            echo "<p class='info'>You may need to import your schema manually</p>";
        }
        
        return true;
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Database test failed: " . $e->getMessage() . "</p>";
        return false;
    }
}

echo "<div class='step'>";
echo "<h2>Next Steps</h2>";
echo "<div class='code'>";
echo "<strong>If everything is working:</strong><br>";
echo "1. Your database is ready to use<br>";
echo "2. Test the application functionality<br>";
echo "3. Create test users if needed<br><br>";

echo "<strong>If you encountered errors:</strong><br>";
echo "1. Check XAMPP Control Panel - ensure MySQL is running<br>";
echo "2. Try restarting XAMPP services<br>";
echo "3. Check the troubleshooting script: <a href='test_database_connection.php'>test_database_connection.php</a><br>";
echo "4. Manually create database using phpMyAdmin<br>";
echo "5. Import schema files manually<br>";
echo "</div>";

echo "<h3>Useful Links:</h3>";
echo "<ul>";
echo "<li><a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a> - Database management</li>";
echo "<li><a href='test_database_connection.php'>Database Connection Test</a> - Troubleshooting</li>";
echo "<li><a href='test_smtp_config.php'>SMTP Configuration Test</a> - Email setup</li>";
echo "</ul>";
echo "</div>";

echo "<p><strong>Database setup completed at " . date('Y-m-d H:i:s') . "</strong></p>";
?>

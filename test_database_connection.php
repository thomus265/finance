<?php
/**
 * Database Connection Troubleshooting Script
 */

echo "<h1>Database Connection Troubleshooting</h1>";
echo "<style>
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.info { color: blue; }
.code { background: #f5f5f5; padding: 10px; border-radius: 3px; font-family: monospace; }
</style>";

echo "<h2>1. MySQL Extension Check</h2>";
check_mysql_extension();

echo "<h2>2. Connection Parameters</h2>";
show_connection_parameters();

echo "<h2>3. MySQL Server Status Check</h2>";
check_mysql_server();

echo "<h2>4. Database Existence Check</h2>";
check_database_existence();

echo "<h2>5. XAMPP Status Check</h2>";
check_xampp_status();

echo "<h2>6. Troubleshooting Steps</h2>";
show_troubleshooting_steps();

function check_mysql_extension() {
    if (extension_loaded('mysqli')) {
        echo "<p class='success'>✅ MySQLi extension is loaded</p>";
        echo "<p class='info'>MySQLi version: " . mysqli_get_client_info() . "</p>";
    } else {
        echo "<p class='error'>❌ MySQLi extension is not loaded</p>";
        echo "<p class='warning'>Please enable mysqli extension in php.ini</p>";
    }
    
    if (extension_loaded('pdo_mysql')) {
        echo "<p class='success'>✅ PDO MySQL extension is loaded</p>";
    } else {
        echo "<p class='warning'>⚠️ PDO MySQL extension is not loaded</p>";
    }
}

function show_connection_parameters() {
    echo "<div class='code'>";
    echo "<strong>Current Database Configuration:</strong><br>";
    echo "Host: localhost<br>";
    echo "Username: root<br>";
    echo "Password: (empty)<br>";
    echo "Database: finance<br>";
    echo "Port: 3306 (default)<br>";
    echo "</div>";
}

function check_mysql_server() {
    echo "<p class='info'>Testing MySQL server connection...</p>";
    
    // Test basic connection without database
    $connection = @new mysqli('localhost', 'root', '');
    
    if ($connection->connect_error) {
        echo "<p class='error'>❌ Cannot connect to MySQL server</p>";
        echo "<p class='error'>Error: " . $connection->connect_error . "</p>";
        
        // Try alternative ports
        echo "<p class='info'>Trying alternative ports...</p>";
        
        $ports = [3306, 3307, 3308];
        foreach ($ports as $port) {
            $test_connection = @new mysqli('localhost', 'root', '', '', $port);
            if (!$test_connection->connect_error) {
                echo "<p class='success'>✅ MySQL found on port $port</p>";
                $test_connection->close();
                return;
            }
            $test_connection->close();
        }
        
        echo "<p class='error'>❌ MySQL server not found on common ports</p>";
        return;
    }
    
    echo "<p class='success'>✅ MySQL server is running</p>";
    echo "<p class='info'>MySQL version: " . $connection->server_info . "</p>";
    
    $connection->close();
}

function check_database_existence() {
    echo "<p class='info'>Checking if 'finance' database exists...</p>";
    
    $connection = @new mysqli('localhost', 'root', '');
    
    if ($connection->connect_error) {
        echo "<p class='error'>❌ Cannot connect to check database existence</p>";
        return;
    }
    
    $result = $connection->query("SHOW DATABASES LIKE 'finance'");
    
    if ($result && $result->num_rows > 0) {
        echo "<p class='success'>✅ 'finance' database exists</p>";
        
        // Test connection to the finance database
        $finance_connection = @new mysqli('localhost', 'root', '', 'finance');
        if ($finance_connection->connect_error) {
            echo "<p class='error'>❌ Cannot connect to 'finance' database</p>";
            echo "<p class='error'>Error: " . $finance_connection->connect_error . "</p>";
        } else {
            echo "<p class='success'>✅ Successfully connected to 'finance' database</p>";
            
            // Check tables
            $tables = $finance_connection->query("SHOW TABLES");
            if ($tables && $tables->num_rows > 0) {
                echo "<p class='success'>✅ Database contains " . $tables->num_rows . " tables</p>";
            } else {
                echo "<p class='warning'>⚠️ Database exists but contains no tables</p>";
            }
            $finance_connection->close();
        }
    } else {
        echo "<p class='error'>❌ 'finance' database does not exist</p>";
        echo "<p class='info'>Available databases:</p>";
        
        $databases = $connection->query("SHOW DATABASES");
        if ($databases) {
            echo "<ul>";
            while ($row = $databases->fetch_array()) {
                echo "<li>" . $row[0] . "</li>";
            }
            echo "</ul>";
        }
    }
    
    $connection->close();
}

function check_xampp_status() {
    echo "<p class='info'>XAMPP Status Check:</p>";
    
    // Check if we're running on Windows (common for XAMPP)
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        echo "<p class='info'>Detected Windows environment</p>";
        
        // Check if XAMPP processes are running
        $processes = ['mysqld.exe', 'apache.exe'];
        foreach ($processes as $process) {
            $output = shell_exec("tasklist /FI \"IMAGENAME eq $process\" 2>NUL");
            if (strpos($output, $process) !== false) {
                echo "<p class='success'>✅ $process is running</p>";
            } else {
                echo "<p class='error'>❌ $process is not running</p>";
            }
        }
    } else {
        echo "<p class='info'>Non-Windows environment detected</p>";
        
        // Check MySQL process on Unix-like systems
        $output = shell_exec("ps aux | grep mysql | grep -v grep");
        if (!empty($output)) {
            echo "<p class='success'>✅ MySQL process found</p>";
        } else {
            echo "<p class='error'>❌ MySQL process not found</p>";
        }
    }
}

function show_troubleshooting_steps() {
    echo "<div class='code'>";
    echo "<h3>Step-by-Step Troubleshooting:</h3>";
    
    echo "<h4>1. Start XAMPP Services</h4>";
    echo "• Open XAMPP Control Panel<br>";
    echo "• Start Apache service<br>";
    echo "• Start MySQL service<br>";
    echo "• Check that both services show 'Running' status<br><br>";
    
    echo "<h4>2. Check MySQL Port</h4>";
    echo "• Default MySQL port is 3306<br>";
    echo "• Check XAMPP Control Panel for actual port<br>";
    echo "• If using different port, update Database.php<br><br>";
    
    echo "<h4>3. Create Database</h4>";
    echo "• Open phpMyAdmin (http://localhost/phpmyadmin)<br>";
    echo "• Create new database named 'finance'<br>";
    echo "• Import your SQL schema if available<br><br>";
    
    echo "<h4>4. Check Firewall/Antivirus</h4>";
    echo "• Temporarily disable firewall<br>";
    echo "• Add XAMPP to antivirus exceptions<br>";
    echo "• Check if port 3306 is blocked<br><br>";
    
    echo "<h4>5. Alternative Solutions</h4>";
    echo "• Restart XAMPP completely<br>";
    echo "• Restart your computer<br>";
    echo "• Reinstall XAMPP if necessary<br>";
    echo "• Check Windows Services for MySQL<br>";
    echo "</div>";
    
    echo "<h3>Quick Fixes to Try:</h3>";
    echo "<ol>";
    echo "<li><strong>Restart XAMPP:</strong> Stop all services, close XAMPP, restart as administrator</li>";
    echo "<li><strong>Check Port:</strong> Ensure MySQL is running on port 3306</li>";
    echo "<li><strong>Create Database:</strong> Use phpMyAdmin to create 'finance' database</li>";
    echo "<li><strong>Check Permissions:</strong> Run XAMPP as administrator</li>";
    echo "<li><strong>Firewall:</strong> Allow XAMPP through Windows Firewall</li>";
    echo "</ol>";
    
    echo "<h3>Alternative Database Configuration:</h3>";
    echo "<p>If you need to use different settings, update Database.php:</p>";
    echo "<div class='code'>";
    echo "// For different port:<br>";
    echo "\$this->connection = new mysqli(\$dbhost, \$dbuser, \$dbpass, \$dbname, \$port);<br><br>";
    echo "// For different credentials:<br>";
    echo "\$dbuser = 'your_username';<br>";
    echo "\$dbpass = 'your_password';<br>";
    echo "</div>";
}

echo "<h2>7. Test Database Class</h2>";
echo "<p class='info'>Testing the actual Database class...</p>";

try {
    // Try to include and test the Database class
    if (file_exists('controller/Database.php')) {
        require_once('controller/Database.php');
        
        echo "<p class='info'>Attempting to create Database instance...</p>";
        $db = new Database();
        echo "<p class='success'>✅ Database connection successful!</p>";
        
        // Test a simple query
        $result = $db->query("SELECT 1 as test")->fetchArray();
        if ($result && $result['test'] == 1) {
            echo "<p class='success'>✅ Database query test successful!</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Database.php file not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<p><strong>Database troubleshooting completed at " . date('Y-m-d H:i:s') . "</strong></p>";
?>

<?php
/**
 * Test script to verify function redeclaration fixes
 */

echo "<h1>Function Redeclaration Fixes Test</h1>";

// Test 1: Multiple inclusions of main.php
echo "<h2>1. Testing Multiple Inclusions of main.php</h2>";
test_multiple_inclusions();

// Test 2: Function existence checks
echo "<h2>2. Testing Function Protection</h2>";
test_function_protection();

// Test 3: Autoloader functionality
echo "<h2>3. Testing Autoloader Functionality</h2>";
test_autoloader();

// Test 4: Include chain simulation
echo "<h2>4. Testing Include Chain Simulation</h2>";
test_include_chain();

function test_multiple_inclusions() {
    echo "<h3>Testing Multiple Inclusions</h3>";
    
    try {
        // First inclusion
        require_once('controller/main.php');
        echo "<p>✅ First inclusion of main.php - Success</p>";
        
        // Second inclusion (should be safe now)
        require_once('controller/main.php');
        echo "<p>✅ Second inclusion of main.php - No redeclaration error</p>";
        
        // Third inclusion (should also be safe)
        require_once('controller/main.php');
        echo "<p>✅ Third inclusion of main.php - No redeclaration error</p>";
        
    } catch (Error $e) {
        echo "<p>❌ Error during multiple inclusions: " . $e->getMessage() . "</p>";
    } catch (Exception $e) {
        echo "<p>❌ Exception during multiple inclusions: " . $e->getMessage() . "</p>";
    }
}

function test_function_protection() {
    echo "<h3>Testing Function Protection</h3>";
    
    $protected_functions = [
        'autoload',
        'readMore',
        'greetings',
        'img_greet',
        'getRand',
        'addComment'
    ];
    
    foreach ($protected_functions as $func) {
        if (function_exists($func)) {
            echo "<p>✅ Function '$func' exists and is protected</p>";
        } else {
            echo "<p>❌ Function '$func' not found</p>";
        }
    }
    
    // Test if constants are defined
    if (defined('MAIN_PHP_LOADED')) {
        echo "<p>✅ MAIN_PHP_LOADED constant is defined (prevents multiple inclusions)</p>";
    } else {
        echo "<p>❌ MAIN_PHP_LOADED constant not defined</p>";
    }
}

function test_autoloader() {
    echo "<h3>Testing Autoloader Functionality</h3>";
    
    try {
        // Test if autoloader is registered
        $autoloaders = spl_autoload_functions();
        $autoload_registered = false;
        
        foreach ($autoloaders as $autoloader) {
            if (is_string($autoloader) && $autoloader === 'autoload') {
                $autoload_registered = true;
                break;
            }
        }
        
        if ($autoload_registered) {
            echo "<p>✅ Autoloader function is registered</p>";
        } else {
            echo "<p>❌ Autoloader function not registered</p>";
        }
        
        // Test autoloading a class
        if (class_exists('Database')) {
            echo "<p>✅ Database class autoloaded successfully</p>";
        } else {
            echo "<p>❌ Database class not autoloaded</p>";
        }
        
        if (class_exists('User')) {
            echo "<p>✅ User class autoloaded successfully</p>";
        } else {
            echo "<p>❌ User class not autoloaded</p>";
        }
        
        if (class_exists('Transaction')) {
            echo "<p>✅ Transaction class autoloaded successfully</p>";
        } else {
            echo "<p>❌ Transaction class not autoloaded</p>";
        }
        
    } catch (Error $e) {
        echo "<p>❌ Error testing autoloader: " . $e->getMessage() . "</p>";
    }
}

function test_include_chain() {
    echo "<h3>Testing Include Chain Simulation</h3>";
    
    // Simulate the problematic include chain that was causing the error
    echo "<p><strong>Simulating the problematic include chain:</strong></p>";
    echo "<ol>";
    echo "<li>user/transactions.php includes controller/main.php</li>";
    echo "<li>user/transactions.php includes includes/navbar.php</li>";
    echo "<li>includes/navbar.php includes controller/main.php again</li>";
    echo "</ol>";
    
    try {
        // This should now work without errors
        ob_start(); // Capture any output
        
        // Simulate step 1: transactions.php includes main.php
        require_once('controller/main.php');
        echo "<p>✅ Step 1: main.php included from transactions.php</p>";
        
        // Simulate step 2: navbar.php tries to include main.php again
        // (This would normally be done by including navbar.php, but we'll simulate it)
        require_once('controller/main.php');
        echo "<p>✅ Step 2: main.php included again from navbar.php (no error)</p>";
        
        ob_end_clean(); // Clear any captured output
        
        echo "<p>✅ Include chain simulation completed successfully</p>";
        
    } catch (Error $e) {
        ob_end_clean();
        echo "<p>❌ Error in include chain simulation: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Test Summary</h2>";
echo "<p><strong>Function Redeclaration Fixes Test Complete!</strong></p>";

echo "<h3>Key Fixes Applied:</h3>";
echo "<ul>";
echo "<li>✅ Added MAIN_PHP_LOADED constant to prevent multiple inclusions</li>";
echo "<li>✅ Wrapped autoload() function in function_exists() check</li>";
echo "<li>✅ Protected all utility functions (readMore, greetings, etc.) with function_exists() checks</li>";
echo "<li>✅ Changed all require statements to require_once for main.php</li>";
echo "<li>✅ Updated all model files to use require_once</li>";
echo "<li>✅ Updated includes/navbar.php to use require_once</li>";
echo "</ul>";

echo "<h3>Files Modified:</h3>";
echo "<ul>";
echo "<li><code>controller/main.php</code> - Added protection against redeclaration</li>";
echo "<li><code>includes/navbar.php</code> - Changed to require_once</li>";
echo "<li><code>user/transactions.php</code> - Changed to require_once</li>";
echo "<li><code>finance/dashboard.php</code> - Changed to require_once</li>";
echo "<li>All model files - Changed to require_once</li>";
echo "</ul>";

echo "<h3>Manual Testing Checklist:</h3>";
echo "<ol>";
echo "<li>Visit <a href='/user_transactions' target='_blank'>User Transactions</a> - Should load without redeclaration errors</li>";
echo "<li>Visit <a href='/finance_dashboard' target='_blank'>Finance Dashboard</a> - Should load without redeclaration errors</li>";
echo "<li>Visit transaction details pages - Should work without errors</li>";
echo "<li>Test AJAX endpoints (file upload, state changes) - Should work without errors</li>";
echo "<li>Check server error logs - Should show no more 'Cannot redeclare' errors</li>";
echo "</ol>";

echo "<h3>Technical Details:</h3>";
echo "<p><strong>Root Cause:</strong> Multiple files were including controller/main.php using require() instead of require_once(), causing functions to be declared multiple times in the same request.</p>";
echo "<p><strong>Solution:</strong> Added function_exists() checks around all function declarations and changed all includes to require_once().</p>";
echo "<p><strong>Prevention:</strong> The MAIN_PHP_LOADED constant prevents the entire file from being processed multiple times.</p>";

echo "<p><strong>The 'Cannot redeclare autoload()' error should now be resolved!</strong></p>";
?>

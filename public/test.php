<?php
// public/test.php - Simplified test page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Loan System - System Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        h1 { color: #333; }
        h2 { color: #444; margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .success { color: green; }
        .warning { color: orange; }
        .error { color: red; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f5f5f5; }
        .btn { display: inline-block; padding: 8px 15px; margin: 5px; background: #007bff; color: white; 
               text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #0056b3; }
        .status-box { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .status-success { background-color: #d4edda; border: 1px solid #c3e6cb; }
        .status-warning { background-color: #fff3cd; border: 1px solid #ffeaa7; }
        .status-error { background-color: #f8d7da; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <h1>🏦 Bank Loan Management System - System Test</h1>
    <p><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>

    <?php
    // Check if vendor autoload exists
    $hasVendor = file_exists(__DIR__ . '/../vendor/autoload.php');
    
    if ($hasVendor) {
        require_once __DIR__ . '/../vendor/autoload.php';
        echo "<p class='success'>✅ Vendor autoload loaded successfully</p>";
    } else {
        echo "<p class='error'>❌ Vendor directory not found. Run 'composer install'.</p>";
    }
    
    // Load environment
    $envPath = __DIR__ . '/../.env';
    if (file_exists($envPath)) {
        try {
            Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
            echo "<p class='success'>✅ Environment variables loaded</p>";
        } catch (Exception $e) {
            echo "<p class='warning'>⚠️ Environment loading failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='warning'>⚠️ .env file not found</p>";
    }
    ?>

    <h2>📊 Database Connection Test</h2>
    
    <?php
    $dbHealthy = false;
    $currentClientType = 'none';
    
    if ($hasVendor) {
        try {
            require_once __DIR__ . '/../src/Services/DatabaseService.php';
            
            // Test database service
            $db = App\Services\DatabaseService::getInstance();
            $currentClientType = $db->getClientType();
            
            echo "<div class='status-box status-success'>";
            echo "<h3>✅ Database Service Initialized</h3>";
            echo "<p><strong>Client Type:</strong> " . $currentClientType . "</p>";
            
            // Test connection
            if ($db->ping()) {
                $dbHealthy = true;
                echo "<p><strong>Connection Status:</strong> <span class='success'>CONNECTED</span></p>";
                
                // Test basic operations
                echo "<h4>Testing Database Operations:</h4>";
                
                try {
                    $testDoc = [
                        'test_type' => 'connection_test',
                        'timestamp' => time(),
                        'random_value' => uniqid()
                    ];
                    
                    $insertResult = $db->insertOne('system_settings', $testDoc);
                    echo "<p><strong>Insert:</strong> " . ($insertResult['insertedCount'] === 1 ? '✅ PASS' : '❌ FAIL') . "</p>";
                    
                    if ($insertResult['insertedCount'] === 1) {
                        $foundDoc = $db->findOne('system_settings', ['test_type' => 'connection_test']);
                        echo "<p><strong>Find:</strong> " . ($foundDoc ? '✅ PASS' : '❌ FAIL') . "</p>";
                        
                        $count = $db->count('system_settings', ['test_type' => 'connection_test']);
                        echo "<p><strong>Count:</strong> " . ($count >= 1 ? '✅ PASS (' . $count . ')' : '❌ FAIL') . "</p>";
                        
                        // Cleanup
                        $db->deleteMany('system_settings', ['test_type' => 'connection_test']);
                    }
                } catch (Exception $e) {
                    echo "<p class='error'>❌ Operations test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                
            } else {
                echo "<p><strong>Connection Status:</strong> <span class='error'>FAILED</span></p>";
                $dbHealthy = false;
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='status-box status-error'>";
            echo "<h3>❌ Database Service Failed</h3>";
            echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
    }
    ?>

    <h2>⚙️ Environment Configuration</h2>
    
    <?php
    echo "<table>";
    echo "<tr><th>Variable</th><th>Value</th><th>Status</th></tr>";

    $importantVars = [
        'APP_ENV',
        'APP_DEBUG',
        'MONGODB_URI',
        'DB_DATABASE',
        'MAIL_HOST',
        'MAIL_PORT',
        'JWT_SECRET',
        'CORS_ALLOWED_ORIGINS'
    ];

    foreach ($importantVars as $var) {
        $value = getenv($var);
        $isSet = !empty($value) && $value !== 'null' && $value !== 'undefined';
        $status = $isSet ? '✅ Set' : '❌ Missing';
        $displayValue = $value ? (in_array($var, ['JWT_SECRET', 'MONGODB_URI']) ? '••••••••' : htmlspecialchars($value)) : 'Not set';
        echo "<tr><td><strong>$var</strong></td><td>$displayValue</td><td>$status</td></tr>";
    }

    echo "</table>";
    ?>

    <h2>📋 System Health Summary</h2>
    
    <?php
    $healthStatus = $dbHealthy ? 'healthy' : 'unhealthy';
    $statusClass = $healthStatus === 'healthy' ? 'status-success' : 'status-error';
    
    echo "<div class='status-box $statusClass'>";
    echo "<h3>System Status: " . strtoupper($healthStatus) . "</h3>";
    echo "<p><strong>Database:</strong> " . ($dbHealthy ? '✅ Healthy' : '❌ Unhealthy') . "</p>";
    echo "<p><strong>Dependencies:</strong> " . ($hasVendor ? '✅ Loaded' : '❌ Missing') . "</p>";
    echo "<p><strong>Client Type:</strong> " . $currentClientType . "</p>";
    echo "</div>";
    ?>

    <h2>🔧 Quick Actions</h2>
    <p>
        <a class="btn" href="test.php?health=1">Health Check (JSON)</a>
        <a class="btn" href="test.php?phpinfo=1">PHP Info</a>
        <a class="btn" href="test-db.php">Database Test</a>
    </p>

    <hr>
    <p><strong>Note:</strong> This test page should be removed or protected in production environments.</p>
    
    <?php
    // JSON health endpoint
    if (isset($_GET['health'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => $healthStatus,
            'timestamp' => date('c'),
            'services' => [
                'database' => $dbHealthy,
                'dependencies' => $hasVendor
            ],
            'client_type' => $currentClientType
        ]);
        exit;
    }
    ?>
</body>
</html>
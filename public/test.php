<?php
// public/test.php - Comprehensive test file with fallback testing
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Loan System - Comprehensive Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            line-height: 1.6;
        }

        h1 {
            color: #333;
        }

        h2 {
            color: #444;
            margin-top: 30px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }

        .success {
            color: green;
        }

        .warning {
            color: orange;
        }

        .error {
            color: red;
        }

        .info {
            color: blue;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin: 15px 0;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f5f5f5;
        }

        pre {
            background-color: #f8f8f8;
            padding: 15px;
            overflow: auto;
        }

        .btn {
            display: inline-block;
            padding: 8px 15px;
            margin: 5px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .btn:hover {
            background: #0056b3;
        }

        .alert {
            background-color: #e7f3fe;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 15px 0;
        }

        .status-box {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }

        .status-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }

        .status-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
        }

        .status-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <h1>🏦 Bank Loan Management System - Comprehensive Test</h1>
    <p><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
    <p><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>

    <?php
    $hasVendor = file_exists(__DIR__ . '/../vendor/autoload.php');
    $overallHealth = true;
    $testResults = [];

    // Load dependencies
    if ($hasVendor) {
        require_once __DIR__ . '/../vendor/autoload.php';
        echo "<p class='success'>✅ Vendor autoload loaded successfully</p>";
    } else {
        echo "<p class='error'>❌ Vendor directory not found. Run 'composer install'.</p>";
        $overallHealth = false;
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

    <h2>📊 Database Connection Test with Fallbacks</h2>

    <?php
    $dbResults = [];
    $dbHealthy = false;
    $currentClientType = 'none';

    if ($hasVendor) {
        try {
            require_once __DIR__ . '/../src/Services/DatabaseService.php';

            // Test database service
            $db = App\Services\DatabaseService::getInstance();
            $currentClientType = $db->getClientType();

            echo "<div class='status-box status-success'>";
            echo "<h3>✅ Database Service Initialized Successfully</h3>";
            echo "<p><strong>Client Type:</strong> " . $currentClientType . "</p>";

            // Test connection
            if ($db->ping()) {
                $dbHealthy = true;
                echo "<p><strong>Connection Status:</strong> <span class='success'>CONNECTED</span></p>";

                // Get database stats
                $stats = $db->getStats();
                echo "<p><strong>Database Name:</strong> " . $stats['database_name'] . "</p>";
                echo "<p><strong>Collections:</strong> " . count($stats['collections']) . "</p>";

                // Test basic operations
                echo "<h4>Testing Database Operations:</h4>";
                $operationResults = [];

                // Test 1: Insert operation
                try {
                    $testDoc = [
                        'test_type' => 'connection_test',
                        'timestamp' => time(),
                        'random_value' => uniqid()
                    ];

                    $insertResult = $db->insertOne('system_settings', $testDoc);
                    if ($insertResult['insertedCount'] === 1) {
                        $operationResults['insert'] = '✅ PASS';

                        // Test 2: Find operation
                        $foundDoc = $db->findOne('system_settings', ['test_type' => 'connection_test']);
                        if ($foundDoc && $foundDoc['test_type'] === 'connection_test') {
                            $operationResults['find'] = '✅ PASS';

                            // Test 3: Update operation
                            $updateResult = $db->updateOne(
                                'system_settings',
                                ['test_type' => 'connection_test'],
                                ['$set' => ['updated' => true]]
                            );
                            if ($updateResult['modifiedCount'] >= 1) {
                                $operationResults['update'] = '✅ PASS';
                            } else {
                                $operationResults['update'] = '❌ FAIL';
                            }

                            // Test 4: Count operation
                            $count = $db->count('system_settings', ['test_type' => 'connection_test']);
                            if ($count >= 1) {
                                $operationResults['count'] = '✅ PASS (' . $count . ')';
                            } else {
                                $operationResults['count'] = '❌ FAIL';
                            }

                            // Test 5: Delete operation (cleanup)
                            $deleteResult = $db->deleteMany('system_settings', ['test_type' => 'connection_test']);
                            if ($deleteResult['deletedCount'] >= 1) {
                                $operationResults['delete'] = '✅ PASS (' . $deleteResult['deletedCount'] . ')';
                            } else {
                                $operationResults['delete'] = '❌ FAIL';
                            }

                        } else {
                            $operationResults['find'] = '❌ FAIL';
                        }
                    } else {
                        $operationResults['insert'] = '❌ FAIL';
                    }
                } catch (Exception $e) {
                    $operationResults['operations'] = '❌ FAIL: ' . $e->getMessage();
                }

                // Display operation results
                foreach ($operationResults as $op => $result) {
                    echo "<p><strong>" . ucfirst($op) . ":</strong> $result</p>";
                }

                // Performance test
                echo "<h4>Performance Test (10 documents):</h4>";
                try {
                    $perfStart = microtime(true);

                    $testDocs = [];
                    for ($i = 0; $i < 10; $i++) {
                        $testDocs[] = [
                            'perf_test' => true,
                            'index' => $i,
                            'data' => 'performance_test_' . uniqid()
                        ];
                    }

                    $bulkInsert = $db->insertMany('system_settings', $testDocs);
                    $insertTime = microtime(true) - $perfStart;

                    $findStart = microtime(true);
                    $foundDocs = $db->find('system_settings', ['perf_test' => true]);
                    $findTime = microtime(true) - $findStart;

                    $deleteStart = microtime(true);
                    $bulkDelete = $db->deleteMany('system_settings', ['perf_test' => true]);
                    $deleteTime = microtime(true) - $deleteStart;

                    echo "<p>Bulk Insert: " . $bulkInsert['insertedCount'] . " docs in " . number_format($insertTime * 1000, 2) . "ms</p>";
                    echo "<p>Bulk Find: " . count($foundDocs) . " docs in " . number_format($findTime * 1000, 2) . "ms</p>";
                    echo "<p>Bulk Delete: " . $bulkDelete['deletedCount'] . " docs in " . number_format($deleteTime * 1000, 2) . "ms</p>";

                } catch (Exception $e) {
                    echo "<p class='error'>Performance test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
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
            $overallHealth = false;
        }
    } else {
        echo "<p class='error'>❌ Cannot test database without dependencies</p>";
        $overallHealth = false;
    }

    // Test individual client types
    echo "<h3>Individual Client Type Testing</h3>";

    // Test MongoDB Library
    if (class_exists('MongoDB\Client')) {
        try {
            $mongoUri = getenv('MONGODB_URI');
            if ($mongoUri) {
                $mongoClient = new MongoDB\Client($mongoUri, [
                    'connectTimeoutMS' => 5000,
                    'serverSelectionTimeoutMS' => 5000,
                ]);
                $mongoClient->selectDatabase('admin')->command(['ping' => 1]);
                echo "<p class='success'>✅ MongoDB Library Client: WORKING</p>";
            } else {
                echo "<p class='warning'>⚠️ MongoDB Library Client: No URI configured</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ MongoDB Library Client: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='warning'>⚠️ MongoDB Library: Not available</p>";
    }

    // Test Custom MongoDB Client
    if (extension_loaded('mongodb')) {
        try {
            $mongoUri = getenv('MONGODB_URI');
            if ($mongoUri) {
                require_once __DIR__ . '/../src/Database/MongoClient.php';
                $customClient = new App\Database\MongoClient($mongoUri, 'test');
                $customClient->ping();
                echo "<p class='success'>✅ Custom MongoDB Client: WORKING</p>";
            } else {
                echo "<p class='warning'>⚠️ Custom MongoDB Client: No URI configured</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Custom MongoDB Client: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='error'>❌ MongoDB Extension: Not loaded</p>";
    }

    // Test File Storage Fallback
    try {
        require_once __DIR__ . '/../src/Database/FileStorageClient.php';
        $fileClient = new App\Database\FileStorageClient('test');
        $fileClient->ping();
        echo "<p class='success'>✅ File Storage Fallback Client: WORKING</p>";

        // Test file storage operations
        $testDoc = ['test' => 'file_storage_test', 'timestamp' => time()];
        $insertResult = $fileClient->insertOne('test_collection', $testDoc);
        $foundDoc = $fileClient->findOne('test_collection', ['test' => 'file_storage_test']);
        $deleteResult = $fileClient->deleteOne('test_collection', ['test' => 'file_storage_test']);

        if ($insertResult['insertedCount'] === 1 && $foundDoc && $deleteResult['deletedCount'] === 1) {
            echo "<p class='info'>  → File operations test: PASSED</p>";
        }

    } catch (Exception $e) {
        echo "<p class='error'>❌ File Storage Client: " . $e->getMessage() . "</p>";
        $overallHealth = false;
    }
    ?>

    <h2>📧 Email Service Test</h2>

    <?php
    $emailVars = ['MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD'];
    $missingEmailVars = array_filter($emailVars, function ($var) {
        $value = getenv($var);
        return empty($value) || $value === 'null';
    });

    if (!empty($missingEmailVars)) {
        echo "<div class='status-box status-warning'>";
        echo "<h3>⚠️ Email Configuration Incomplete</h3>";
        echo "<p>Missing variables: " . implode(', ', $missingEmailVars) . "</p>";
        echo "</div>";
    } else {
        echo "<div class='status-box status-success'>";
        echo "<h3>✅ Email Configuration Complete</h3>";
        echo "<p><strong>Host:</strong> " . getenv('MAIL_HOST') . "</p>";
        echo "<p><strong>Port:</strong> " . getenv('MAIL_PORT') . "</p>";
        echo "<p><strong>Encryption:</strong> " . (getenv('MAIL_ENCRYPTION') ?: 'None') . "</p>";

        if ($hasVendor && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = getenv('MAIL_HOST');
                $mail->SMTPAuth = true;
                $mail->Username = getenv('MAIL_USERNAME');
                $mail->Password = getenv('MAIL_PASSWORD');
                $mail->SMTPSecure = getenv('MAIL_ENCRYPTION') ?: 'tls';
                $mail->Port = (int) getenv('MAIL_PORT');
                $mail->Timeout = 10;
                $mail->SMTPDebug = 0;

                // Test connection only (don't send email unless requested)
                if ($mail->smtpConnect()) {
                    echo "<p class='success'>✅ SMTP Connection: SUCCESSFUL</p>";
                    $mail->smtpClose();

                    // Test email sending if requested
                    if (isset($_GET['send_test_email'])) {
                        $mail->setFrom(
                            getenv('MAIL_FROM_ADDRESS') ?: getenv('MAIL_USERNAME'),
                            getenv('MAIL_FROM_NAME') ?: 'Test System'
                        );
                        $mail->addAddress(getenv('MAIL_USERNAME'), 'Test Recipient');
                        $mail->isHTML(true);
                        $mail->Subject = 'Test Email from PHP Backend - ' . date('Y-m-d H:i:s');
                        $mail->Body = '<h1>Test Email</h1><p>This is a test email from your PHP backend on Render.</p>';
                        $mail->AltBody = 'This is a test email from your PHP backend on Render.';

                        if ($mail->send()) {
                            echo "<p class='success'>✅ Test Email: SENT SUCCESSFULLY</p>";
                        } else {
                            echo "<p class='error'>❌ Test Email: FAILED - " . htmlspecialchars($mail->ErrorInfo) . "</p>";
                        }
                    } else {
                        echo "<p><a class='btn' href='test.php?send_test_email=1'>Send Test Email</a></p>";
                    }
                } else {
                    echo "<p class='error'>❌ SMTP Connection: FAILED</p>";
                }

            } catch (Exception $e) {
                echo "<p class='error'>❌ Email Test: FAILED - " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p class='warning'>⚠️ Cannot test email without PHPMailer dependencies</p>";
        }
        echo "</div>";
    }
    ?>

    <h2>🔐 JWT & Authentication Test</h2>

    <?php
    $jwtSecret = getenv('JWT_SECRET');
    if (empty($jwtSecret)) {
        echo "<div class='status-box status-error'>";
        echo "<h3>❌ JWT Secret Missing</h3>";
        echo "<p>JWT_SECRET environment variable is not set</p>";
        echo "</div>";
        $overallHealth = false;
    } else {
        echo "<div class='status-box status-success'>";
        echo "<h3>✅ JWT Configuration Complete</h3>";
        echo "<p><strong>Secret:</strong> " . (strlen($jwtSecret) > 10 ? substr($jwtSecret, 0, 10) . '...' : 'Too short!') . "</p>";

        if ($hasVendor && class_exists('Firebase\JWT\JWT')) {
            try {
                // Test JWT encoding/decoding
                $payload = [
                    'user_id' => 123,
                    'email' => 'test@example.com',
                    'iat' => time(),
                    'exp' => time() + 3600
                ];

                $token = Firebase\JWT\JWT::encode($payload, $jwtSecret, 'HS256');
                $decoded = Firebase\JWT\JWT::decode($token, new Firebase\JWT\Key($jwtSecret, 'HS256'));

                if ($decoded->user_id === 123) {
                    echo "<p class='success'>✅ JWT Encoding/Decoding: WORKING</p>";
                    echo "<p><strong>Test Token:</strong> " . substr($token, 0, 30) . "...</p>";
                } else {
                    echo "<p class='error'>❌ JWT Test: FAILED</p>";
                }

            } catch (Exception $e) {
                echo "<p class='error'>❌ JWT Test: FAILED - " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p class='warning'>⚠️ Cannot test JWT without Firebase JWT library</p>";
        }
        echo "</div>";
    }
    ?>

    <h2>🌐 CORS & API Configuration</h2>

    <?php
    $corsOrigins = getenv('CORS_ALLOWED_ORIGINS');
    if (empty($corsOrigins)) {
        echo "<div class='status-box status-warning'>";
        echo "<h3>⚠️ CORS Not Configured</h3>";
        echo "<p>CORS_ALLOWED_ORIGINS environment variable is not set</p>";
        echo "</div>";
    } else {
        echo "<div class='status-box status-success'>";
        echo "<h3>✅ CORS Configuration Complete</h3>";
        echo "<p><strong>Allowed Origins:</strong> " . htmlspecialchars($corsOrigins) . "</p>";
        echo "</div>";
    }

    // Test API endpoints
    echo "<h3>API Endpoint Tests</h3>";

    $endpointsToTest = [
        '/' => 'Root endpoint',
        '/health' => 'Health check',
        '/api/loans/products' => 'Loan products (public)'
    ];

    foreach ($endpointsToTest as $endpoint => $description) {
        $url = 'http://' . $_SERVER['HTTP_HOST'] . $endpoint;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            echo "<p class='success'>✅ $description: HTTP $httpCode</p>";
        } else {
            echo "<p class='error'>❌ $description: HTTP $httpCode</p>";
        }
    }
    ?>

    <h2>⚙️ Environment Overview</h2>

    <?php
    echo "<table>";
    echo "<tr><th>Variable</th><th>Value</th><th>Status</th></tr>";

    $importantVars = [
        'APP_ENV',
        'APP_DEBUG',
        'MONGODB_URI',
        'DB_HOST',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',
        'MAIL_HOST',
        'MAIL_PORT',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
        'MAIL_ENCRYPTION',
        'JWT_SECRET',
        'CORS_ALLOWED_ORIGINS'
    ];

    foreach ($importantVars as $var) {
        $value = getenv($var);
        $isSet = !empty($value) && $value !== 'null' && $value !== 'undefined';
        $status = $isSet ? '✅ Set' : '❌ Missing';
        $displayValue = $value ? (in_array($var, ['MAIL_PASSWORD', 'JWT_SECRET', 'DB_PASSWORD', 'MONGODB_URI']) ? '••••••••' : htmlspecialchars($value)) : 'Not set';
        echo "<tr><td><strong>$var</strong></td><td>$displayValue</td><td>$status</td></tr>";
    }

    echo "</table>";

    // Debug mode: show all environment variables
    if (getenv('APP_DEBUG') === 'true' || isset($_GET['show_all'])) {
        echo "<h3>🔍 All Environment Variables</h3>";
        echo "<pre>";
        foreach (getenv() as $key => $value) {
            $value = in_array($key, ['MAIL_PASSWORD', 'DB_PASSWORD', 'JWT_SECRET', 'MONGODB_URI']) ? '••••••••' : $value;
            echo htmlspecialchars($key) . " = " . htmlspecialchars($value) . "\n";
        }
        echo "</pre>";
    } else {
        echo "<p><a class='btn' href='test.php?show_all=1'>Show All Environment Variables</a></p>";
    }
    ?>

    <h2>📋 System Health Summary</h2>

    <?php
    $healthStatus = $overallHealth && $dbHealthy ? 'healthy' : ($overallHealth ? 'degraded' : 'unhealthy');
    $statusClass = $healthStatus === 'healthy' ? 'status-success' : ($healthStatus === 'degraded' ? 'status-warning' : 'status-error');

    echo "<div class='status-box $statusClass'>";
    echo "<h3>System Status: " . strtoupper($healthStatus) . "</h3>";
    echo "<p><strong>Database:</strong> " . ($dbHealthy ? '✅ Healthy' : '❌ Unhealthy') . "</p>";
    echo "<p><strong>Email:</strong> " . (empty($missingEmailVars) ? '✅ Configured' : '⚠️ Incomplete') . "</p>";
    echo "<p><strong>JWT:</strong> " . (!empty($jwtSecret) ? '✅ Configured' : '❌ Missing') . "</p>";
    echo "<p><strong>Dependencies:</strong> " . ($hasVendor ? '✅ Loaded' : '❌ Missing') . "</p>";
    echo "<p><strong>Client Type:</strong> " . $currentClientType . "</p>";
    echo "</div>";
    ?>

    <h2>🔧 Quick Actions</h2>
    <p>
        <a class="btn" href="test.php?health=1">Health Check (JSON)</a>
        <a class="btn" href="test.php?phpinfo=1">PHP Info</a>
        <a class="btn" href="debug.php">Debug Page</a>
        <a class="btn" href="mongodb_test.php">MongoDB Test</a>
        <a class="btn" href="test_dates.php">Date Handling Test</a>
        <a class="btn" href="test.php?send_test_email=1">Send Test Email</a>
        <a class="btn" href="test.php?show_all=1">Show All Env Vars</a>
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
                'email' => empty($missingEmailVars),
                'jwt' => !empty($jwtSecret),
                'dependencies' => $hasVendor
            ],
            'client_type' => $currentClientType
        ]);
        exit;
    }
    ?>
</body>

</html>
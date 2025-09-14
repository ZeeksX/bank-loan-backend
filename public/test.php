<?php
// public/test.php - Comprehensive test file for PHP backend
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Backend Deployment Test</title>
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

        .info {
            background-color: #e7f3fe;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>

<body>
    <h1>🚀 PHP Backend Deployment Test</h1>
    <p><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
    <p><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>

    <div class="info">
        <p>This test page helps verify your PHP backend is properly configured and all services are working correctly.
        </p>
    </div>

    <?php
    // Check if vendor autoload exists
    $vendorAutoload = __DIR__ . '/../vendor/autoload.php';
    $hasVendor = file_exists($vendorAutoload);

    if ($hasVendor) {
        require_once $vendorAutoload;
        echo "<p class='success'>✅ Vendor autoload loaded successfully</p>";
    } else {
        echo "<p class='warning'>⚠️ Vendor directory not found. Run 'composer install' to install dependencies.</p>";
    }
    // --- MongoDB Connection Test ---
    echo "<h2>📊 MongoDB Connection Test</h2>";

    $mongoHealthy = false;
    $mongoExtensionLoaded = extension_loaded('mongodb');

    if ($mongoExtensionLoaded) {
        echo "<p class='success'>✅ MongoDB PHP extension is loaded</p>";
        echo "<p><strong>MongoDB Extension Version:</strong> " . (phpversion('mongodb') ?: 'Unknown') . "</p>";

        if (getenv('MONGODB_URI') || (getenv('DB_HOST') && getenv('DB_DATABASE'))) {
            try {
                if (getenv('MONGODB_URI')) {
                    $mongoUri = getenv('MONGODB_URI');
                    echo "<p><strong>MongoDB URI:</strong> " . (strpos($mongoUri, '@') ? 'Configured (with authentication)' : 'Configured (no authentication)') . "</p>";
                    $mongo = new MongoDB\Client($mongoUri);
                } else {
                    $mongoHost = getenv('DB_HOST');
                    $mongoDb = getenv('DB_DATABASE');
                    $mongoUser = getenv('DB_USERNAME');
                    $mongoPass = getenv('DB_PASSWORD');
                    $mongoPort = getenv('DB_PORT') ?: 27017;

                    echo "<p><strong>MongoDB Host:</strong> $mongoHost</p>";
                    echo "<p><strong>MongoDB Database:</strong> $mongoDb</p>";

                    $connectionString = "mongodb://" .
                        ($mongoUser ? "$mongoUser:$mongoPass@" : "") .
                        "$mongoHost:$mongoPort/$mongoDb";
                    $mongo = new MongoDB\Client($connectionString);
                }

                // Try to list databases to test connection
                $dbs = $mongo->listDatabases();
                $mongoHealthy = true;

                // Get database name
                $dbName = getenv('DB_DATABASE') ?: (preg_match('/\/([^?\/]+)(\?|$)/', getenv('MONGODB_URI'), $matches) ? $matches[1] : 'unknown');

                echo "<p class='success'>✅ MongoDB connection: SUCCESSFUL</p>";
                echo "<p class='success'>✅ Connected to database: " . htmlspecialchars($dbName) . "</p>";

            } catch (Exception $e) {
                echo "<p class='error'>❌ MongoDB connection: FAILED - " . htmlspecialchars($e->getMessage()) . "</p>";
                if (strpos($e->getMessage(), 'No suitable servers found') !== false) {
                    echo "<p class='info'>💡 This often indicates a network connectivity issue. Check if your MongoDB server allows connections from Render's IP addresses.</p>";
                }
            }
        } else {
            echo "<p class='warning'>⚠️ MongoDB: No connection credentials configured</p>";
            echo "<p>Set either MONGODB_URI or DB_HOST/DB_DATABASE environment variables.</p>";
        }
    } else {
        echo "<p class='error'>❌ MongoDB PHP extension is not loaded</p>";
        echo "<p>Check your Dockerfile to ensure the MongoDB extension is properly installed and enabled.</p>";
    }

    // --- Email Service Test ---
    echo "<h2>📧 Email Service Test</h2>";

    $emailVars = ['MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD'];
    $missingVars = array_filter($emailVars, function ($var) {
        $value = getenv($var);
        return empty($value) || $value === 'null' || $value === 'undefined';
    });

    if (!empty($missingVars)) {
        echo "<p class='warning'>⚠️ Email: Missing environment variables: " . implode(', ', $missingVars) . "</p>";
    } else {
        echo "<p class='success'>✅ Email: All required environment variables are set</p>";
        echo "<p><strong>Mail Host:</strong> " . getenv('MAIL_HOST') . "</p>";
        echo "<p><strong>Mail Port:</strong> " . getenv('MAIL_PORT') . "</p>";
        echo "<p><strong>Mail Encryption:</strong> " . (getenv('MAIL_ENCRYPTION') ?: 'Not set') . "</p>";

        if ($hasVendor) {
            try {
                if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    throw new Exception("PHPMailer class not found. Run: composer require phpmailer/phpmailer");
                }

                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = getenv('MAIL_HOST');
                $mail->SMTPAuth = true;
                $mail->Username = getenv('MAIL_USERNAME');
                $mail->Password = getenv('MAIL_PASSWORD');
                $mail->SMTPSecure = getenv('MAIL_ENCRYPTION') ?: 'tls';
                $mail->Port = (int) getenv('MAIL_PORT');
                $mail->Timeout = 10; // 10 second timeout
                $mail->SMTPDebug = 0; // Set to 2 for detailed debugging
    
                if ($mail->smtpConnect()) {
                    echo "<p class='success'>✅ SMTP Connection: SUCCESSFUL</p>";
                    $mail->smtpClose();
                } else {
                    echo "<p class='error'>❌ SMTP Connection: FAILED</p>";
                }

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

            } catch (Exception $e) {
                echo "<p class='error'>❌ Email Test: FAILED - " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p class='warning'>⚠️ Cannot test email without vendor dependencies. Run 'composer install' first.</p>";
        }
    }

    // --- Environment Overview ---
    echo "<h2>⚙️ Environment Configuration</h2>";
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
        echo "<h2>🔍 All Environment Variables</h2>";
        echo "<pre>";
        foreach (getenv() as $key => $value) {
            $value = in_array($key, ['MAIL_PASSWORD', 'DB_PASSWORD', 'JWT_SECRET', 'MONGODB_URI']) ? '••••••••' : $value;
            echo htmlspecialchars($key) . " = " . htmlspecialchars($value) . "\n";
        }
        echo "</pre>";
    } else {
        echo "<p><a class='btn' href='test.php?show_all=1'>Show All Environment Variables</a></p>";
    }

    // --- Health Check Endpoint ---
    if (isset($_GET['health'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => $mongoHealthy ? 'healthy' : 'degraded',
            'timestamp' => date('c'),
            'services' => [
                'php' => true,
                'mongodb' => $mongoHealthy,
                'email' => empty($missingVars)
            ]
        ]);
        exit;
    }

    // --- PHP Info ---
    if (isset($_GET['phpinfo'])) {
        phpinfo();
        exit;
    }
    ?>

    <h2>🔧 Quick Actions</h2>
    <p>
        <a class="btn" href="test.php?health=1">Health Check (JSON)</a>
        <a class="btn" href="test.php?phpinfo=1">PHP Info</a>
        <a class="btn" href="debug.php">Debug Page</a>
        <a class="btn" href="mongodb_test.php">MongoDB Test</a>
    </p>

    <hr>
    <p><strong>Note:</strong> This test page should be removed or protected in production environments.</p>
</body>

</html>
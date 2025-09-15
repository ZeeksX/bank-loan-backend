<?php
// public/test.php - Updated to work with MySQL and simplified DatabaseService
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Bank Loan System - System Test</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 36px;
            line-height: 1.6;
            color: #222
        }

        h1 {
            color: #222
        }

        h2 {
            color: #444;
            margin-top: 28px;
            border-bottom: 1px solid #eee;
            padding-bottom: 6px
        }

        .success {
            color: green
        }

        .warning {
            color: orange
        }

        .error {
            color: red
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin: 12px 0
        }

        table,
        th,
        td {
            border: 1px solid #ddd
        }

        th,
        td {
            padding: 10px;
            text-align: left
        }

        th {
            background: #f7f7f7
        }

        .btn {
            display: inline-block;
            padding: 8px 14px;
            margin: 6px;
            background: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px
        }

        .status-box {
            padding: 12px;
            margin: 10px 0;
            border-radius: 6px
        }

        .status-success {
            background: #e9f7ec;
            border: 1px solid #d1edd6
        }

        .status-error {
            background: #fdecea;
            border: 1px solid #f5c6c6
        }

        .muted {
            color: #666;
            font-size: .95rem
        }

        pre {
            background: #fafafa;
            padding: 12px;
            border: 1px solid #eee;
            border-radius: 6px;
            overflow: auto
        }
    </style>
</head>

<body>
    <h1>🏦 Bank Loan Management System — System Test</h1>
    <p class="muted"><strong>Server time:</strong> <?php echo date('Y-m-d H:i:s'); ?> — <strong>PHP:</strong>
        <?php echo phpversion(); ?></p>

    <?php
    // --- basic checks ---
    $hasVendor = file_exists(__DIR__ . '/../vendor/autoload.php');
    if ($hasVendor) {
        require_once __DIR__ . '/../vendor/autoload.php';
        echo "<p class='success'>✅ Composer autoload loaded</p>";
    } else {
        echo "<p class='error'>❌ Composer autoload not found. Run <code>composer install</code></p>";
    }

    // load .env if present
    $envLoaded = false;
    $envPath = __DIR__ . '/../.env';
    if ($hasVendor && file_exists($envPath)) {
        try {
            Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
            $envLoaded = true;
            echo "<p class='success'>✅ Environment variables loaded</p>";
        } catch (Exception $e) {
            echo "<p class='warning'>⚠️ .env load warning: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='warning'>⚠️ .env file not found or composer unavailable</p>";
    }

    // --- try to initialize database service ---
    $dbHealthy = false;
    $clientType = 'none';
    $client = null;
    $service = null;
    $errors = [];

    if ($hasVendor && class_exists('App\\Services\\DatabaseService')) {
        try {
            // Get the service instance
            $service = App\Services\DatabaseService::getInstance();
            $client = $service->client(); // Expect PDO instance
            $clientType = get_class($client); // Should be PDO
        } catch (Exception $e) {
            $errors[] = "Service init failed: " . $e->getMessage();
        }
    } else {
        $errors[] = "App\\Services\\DatabaseService class not available.";
    }

    // --- Database block output ---
    echo "<h2>📊 Database Connection Test</h2>";

    if ($client && is_object($client) && $client instanceof PDO) {
        echo "<div class='status-box status-success'><h3>Database client initialized</h3>";
        echo "<p><strong>Client:</strong> " . htmlspecialchars($clientType) . "</p>";

        // Ping MySQL
        $pingOk = false;
        try {
            $stmt = $client->query('SELECT 1');
            $pingOk = $stmt !== false && $stmt->fetchColumn() === '1';
        } catch (Exception $e) {
            $errors[] = "Ping error: " . $e->getMessage();
        }

        if ($pingOk) {
            $dbHealthy = true;
            echo "<p><strong>Connection status:</strong> <span class='success'>CONNECTED</span></p>";

            // Run a small operations test (insert/select/count/delete)
            echo "<h4>Testing basic operations</h4>";
            try {
                // Insert
                $stmt = $client->prepare("INSERT INTO system_settings (test_type, timestamp, random) VALUES (:test_type, :timestamp, :random)");
                $result = $stmt->execute([
                    'test_type' => 'connection_test',
                    'timestamp' => time(),
                    'random' => uniqid()
                ]);
                echo "<p><strong>Insert:</strong> " . ($result ? "✅ PASS" : "❌ FAIL") . "</p>";

                // Select
                $stmt = $client->prepare("SELECT * FROM system_settings WHERE test_type = :test_type LIMIT 1");
                $stmt->execute(['test_type' => 'connection_test']);
                $found = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "<p><strong>Find:</strong> " . ($found ? "✅ PASS" : "❌ FAIL") . "</p>";

                // Count
                $stmt = $client->prepare("SELECT COUNT(*) FROM system_settings WHERE test_type = :test_type");
                $stmt->execute(['test_type' => 'connection_test']);
                $count = $stmt->fetchColumn();
                echo "<p><strong>Count:</strong> " . ($count >= 1 ? "✅ PASS ({$count})" : "❌ FAIL") . "</p>";

                // Cleanup
                $stmt = $client->prepare("DELETE FROM system_settings WHERE test_type = :test_type");
                $stmt->execute(['test_type' => 'connection_test']);
            } catch (Exception $e) {
                echo "<p class='error'>❌ Operations test error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p><strong>Connection status:</strong> <span class='error'>FAILED</span></p>";
        }

        echo "</div>";
    } else {
        echo "<div class='status-box status-error'><h3>Database client not initialized</h3>";
        foreach ($errors as $err) {
            echo "<p class='error'>" . htmlspecialchars($err) . "</p>";
        }
        echo "</div>";
    }

    // --- environment table ---
    echo "<h2>⚙️ Environment Configuration</h2>";
    echo "<table><tr><th>Variable</th><th>Value</th><th>Status</th></tr>";

    $important = ['APP_ENV', 'APP_DEBUG', 'MYSQL_HOST', 'MYSQL_DATABASE', 'MAIL_HOST', 'MAIL_PORT', 'JWT_SECRET', 'CORS_ALLOWED_ORIGINS'];
    foreach ($important as $v) {
        $val = getenv($v);
        $isSet = ($val !== false && $val !== null && $val !== '');
        $display = $isSet ? (in_array($v, ['JWT_SECRET', 'MYSQL_PASSWORD']) ? '••••••••' : htmlspecialchars($val)) : 'Not set';
        $status = $isSet ? '✅ Set' : '❌ Missing';
        echo "<tr><td><strong>$v</strong></td><td>$display</td><td>$status</td></tr>";
    }
    echo "</table>";

    // --- summary & quick actions ---
    echo "<h2>📋 System Health Summary</h2>";
    $health = $dbHealthy ? 'healthy' : 'unhealthy';
    $cls = $dbHealthy ? 'status-success' : 'status-error';
    echo "<div class='status-box {$cls}'>";
    echo "<h3>System status: " . strtoupper($health) . "</h3>";
    echo "<p><strong>Database:</strong> " . ($dbHealthy ? '✅ Healthy' : '❌ Unhealthy') . "</p>";
    echo "<p><strong>Dependencies:</strong> " . ($hasVendor ? '✅ Loaded' : '❌ Missing') . "</p>";
    echo "<p><strong>Client info:</strong> " . htmlspecialchars($clientType) . "</p>";
    echo "</div>";

    echo "<h2>🔧 Quick Actions</h2>";
    echo "<p><a class='btn' href='test.php?health=1'>Health (JSON)</a>";
    echo "<a class='btn' href='test.php?phpinfo=1'>PHP Info</a></p>";

    // JSON health endpoint
    if (isset($_GET['health'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => $health,
            'timestamp' => date('c'),
            'database' => $dbHealthy ? 'connected' : 'disconnected',
            'dependencies' => $hasVendor,
            'client' => $clientType,
        ], JSON_PRETTY_PRINT);
        exit;
    }

    // phpinfo endpoint (protected by local environment only)
    if (isset($_GET['phpinfo'])) {
        if (php_sapi_name() !== 'cli' && (getenv('APP_ENV') === 'development' || php_sapi_name() === 'cli')) {
            echo '<h2>PHP Info</h2><pre>';
            ob_start();
            phpinfo();
            $p = ob_get_clean();
            echo htmlspecialchars($p);
            echo '</pre>';
        } else {
            echo "<p class='warning'>phpinfo is disabled in non-development environments.</p>";
        }
        exit;
    }
    ?>

    <hr>
    <p class="muted">Note: Remove or protect this page in production.</p>
</body>

</html>
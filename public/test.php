<?php
// public/test.php - Updated to work with the simplified DatabaseService + backwards-compatible
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

    // --- try to initialize database service in a backwards-compatible way ---
    $dbHealthy = false;
    $clientType = 'none';
    $client = null;
    $service = null;
    $errors = [];

    if ($hasVendor && class_exists('App\\Services\\DatabaseService')) {
        try {
            // Get the service instance
            $service = App\Services\DatabaseService::getInstance();

            // Determine client/provider
            if (method_exists($service, 'client')) {
                $client = $service->client(); // new simplified API returns MongoClient wrapper
            } else {
                // older API might return DB-like instance or provide methods on service directly
                $client = $service;
            }

            // Try to detect client type/name
            if (method_exists($service, 'getClientType')) {
                $clientType = $service->getClientType();
            } elseif (is_object($client)) {
                $clientType = get_class($client);
            }

        } catch (Exception $e) {
            $errors[] = "Service init failed: " . $e->getMessage();
        }
    } else {
        $errors[] = "App\\Services\\DatabaseService class not available.";
    }

    // --- Database block output ---
    echo "<h2>📊 Database Connection Test</h2>";

    if ($client && is_object($client)) {
        echo "<div class='status-box status-success'><h3>Database client initialized</h3>";
        echo "<p><strong>Client:</strong> " . htmlspecialchars($clientType) . "</p>";

        // Ping (try multiple strategies depending on available methods)
        $pingOk = false;
        try {
            if (method_exists($client, 'ping')) {
                $pingOk = (bool) $client->ping();
            } elseif (method_exists($service, 'ping')) {
                $pingOk = (bool) $service->ping();
            } else {
                // fallback: attempt a small operation that should exist
                if (method_exists($client, 'listCollections')) {
                    $cols = $client->listCollections();
                    $pingOk = is_array($cols);
                } else {
                    $pingOk = false;
                }
            }
        } catch (Exception $e) {
            $errors[] = "Ping error: " . $e->getMessage();
            $pingOk = false;
        }

        if ($pingOk) {
            $dbHealthy = true;
            echo "<p><strong>Connection status:</strong> <span class='success'>CONNECTED</span></p>";

            // run a small operations test (insert/find/count/delete) — only if methods exist
            echo "<h4>Testing basic operations</h4>";
            try {
                $insertResult = null;

                // prefer insertOne on client, else on service
                if (method_exists($client, 'insertOne')) {
                    $insertResult = $client->insertOne('system_settings', [
                        'test_type' => 'connection_test',
                        'timestamp' => time(),
                        'random' => uniqid()
                    ]);
                } elseif (method_exists($service, 'insertOne')) {
                    $insertResult = $service->insertOne('system_settings', [
                        'test_type' => 'connection_test',
                        'timestamp' => time(),
                        'random' => uniqid()
                    ]);
                }

                if ($insertResult && (isset($insertResult['insertedCount']) ? $insertResult['insertedCount'] === 1 : true)) {
                    echo "<p><strong>Insert:</strong> ✅ PASS</p>";

                    // findOne
                    $found = null;
                    if (method_exists($client, 'findOne')) {
                        $found = $client->findOne('system_settings', ['test_type' => 'connection_test']);
                    } elseif (method_exists($service, 'findOne')) {
                        $found = $service->findOne('system_settings', ['test_type' => 'connection_test']);
                    }

                    echo "<p><strong>Find:</strong> " . ($found ? "✅ PASS" : "❌ FAIL") . "</p>";

                    // count
                    $count = null;
                    if (method_exists($client, 'count')) {
                        $count = $client->count('system_settings', ['test_type' => 'connection_test']);
                    } elseif (method_exists($service, 'count')) {
                        $count = $service->count('system_settings', ['test_type' => 'connection_test']);
                    }
                    echo "<p><strong>Count:</strong> " . (($count !== null && $count >= 1) ? "✅ PASS ({$count})" : "❌ FAIL") . "</p>";

                    // cleanup (deleteMany)
                    if (method_exists($client, 'deleteMany')) {
                        $client->deleteMany('system_settings', ['test_type' => 'connection_test']);
                    } elseif (method_exists($service, 'deleteMany')) {
                        $service->deleteMany('system_settings', ['test_type' => 'connection_test']);
                    }
                } else {
                    echo "<p><strong>Insert:</strong> ❌ SKIPPED/FAILED (method not available or insert failed)</p>";
                }
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

    $important = ['APP_ENV', 'APP_DEBUG', 'MONGODB_URI', 'DB_DATABASE', 'MAIL_HOST', 'MAIL_PORT', 'JWT_SECRET', 'CORS_ALLOWED_ORIGINS'];
    foreach ($important as $v) {
        $val = getenv($v);
        $isSet = ($val !== false && $val !== null && $val !== '');
        $display = $isSet ? (in_array($v, ['JWT_SECRET', 'MONGODB_URI']) ? '••••••••' : htmlspecialchars($val)) : 'Not set';
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
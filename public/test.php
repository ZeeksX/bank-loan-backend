<?php
// public/test.php - Comprehensive test file for MongoDB
echo "<h1>🚀 PHP Backend Deployment Test</h1>";
echo "<p>Server Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Test MongoDB connection
echo "<h2>📊 MongoDB Connection Test</h2>";
if (getenv('MONGODB_URI') || (getenv('DB_HOST') && getenv('DB_DATABASE'))) {
    try {
        require_once __DIR__ . '/../vendor/autoload.php';

        // Use MONGODB_URI if available, otherwise build from individual components
        if (getenv('MONGODB_URI')) {
            $mongoUri = getenv('MONGODB_URI');
            $mongo = new MongoDB\Client($mongoUri);
            echo "<p style='color: green;'>✅ MongoDB URI connection: CONFIGURED</p>";
        } else {
            $mongo = new MongoDB\Client(
                'mongodb://' .
                getenv('DB_USERNAME') . ':' .
                getenv('DB_PASSWORD') . '@' .
                getenv('DB_HOST') . ':' .
                getenv('DB_PORT') . '/' .
                getenv('DB_DATABASE')
            );
            echo "<p style='color: green;'>✅ MongoDB component connection: CONFIGURED</p>";
        }

        // Test if we can connect and list databases
        $databases = $mongo->listDatabases();
        echo "<p style='color: green;'>✅ MongoDB connection: SUCCESS</p>";

        // Get database name
        $dbName = getenv('DB_DATABASE');
        if (empty($dbName)) {
            // Try to extract from URI
            preg_match('/\/([^?\/]+)(\?|$)/', $mongoUri, $matches);
            $dbName = $matches[1] ?? 'unknown';
        }

        echo "<p style='color: green;'>✅ Connected to database: " . htmlspecialchars($dbName) . "</p>";

    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ MongoDB connection: FAILED - " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color: orange;'>ℹ️ MongoDB: No credentials configured</p>";
}

// Test email service
echo "<h2>📧 Email Service Test</h2>";

// Check if email environment variables are set
$emailVars = ['MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD'];
$missingVars = [];

foreach ($emailVars as $var) {
    if (!getenv($var)) {
        $missingVars[] = $var;
    }
}

if (!empty($missingVars)) {
    echo "<p style='color: orange;'>ℹ️ Email: Missing environment variables: " . implode(', ', $missingVars) . "</p>";
} else {
    echo "<p style='color: green;'>✅ Email: All environment variables are set</p>";

    // Test email configuration
    try {
        // Check if PHPMailer is available
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            throw new Exception("PHPMailer not installed. Run: composer require phpmailer/phpmailer");
        }

        require_once __DIR__ . '/../vendor/autoload.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = getenv('MAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('MAIL_USERNAME');
        $mail->Password = getenv('MAIL_PASSWORD');
        $mail->SMTPSecure = getenv('MAIL_ENCRYPTION') ?: 'tls';
        $mail->Port = (int) getenv('MAIL_PORT');

        // Test SMTP connection
        if ($mail->smtpConnect()) {
            echo "<p style='color: green;'>✅ SMTP Connection: SUCCESS</p>";
            $mail->smtpClose();
        } else {
            echo "<p style='color: red;'>❌ SMTP Connection: FAILED</p>";
        }

        // Test sending actual email (only if requested)
        if (isset($_GET['send_test_email'])) {
            echo "<h3>📤 Sending Test Email...</h3>";

            $mail->setFrom(
                getenv('MAIL_FROM_ADDRESS') ?: getenv('MAIL_USERNAME'),
                getenv('MAIL_FROM_NAME') ?: 'Test System'
            );
            $mail->addAddress(getenv('MAIL_USERNAME'), 'Test Recipient');

            $mail->isHTML(true);
            $mail->Subject = 'Test Email from PHP Backend - ' . date('Y-m-d H:i:s');
            $mail->Body = '<h1>Test Email</h1><p>This is a test email sent from your PHP backend deployed on Render.</p><p>If you received this, your email configuration is working correctly!</p>';
            $mail->AltBody = 'This is a test email sent from your PHP backend deployed on Render.';

            if ($mail->send()) {
                echo "<p style='color: green;'>✅ Test Email: SENT SUCCESSFULLY to " . htmlspecialchars(getenv('MAIL_USERNAME')) . "</p>";
            } else {
                echo "<p style='color: red;'>❌ Test Email: FAILED - " . htmlspecialchars($mail->ErrorInfo) . "</p>";
            }
        } else {
            echo "<p>🔗 <a href='test.php?send_test_email=1'>Click here to send a test email</a></p>";
        }

    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Email Test: FAILED - " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Environment overview
echo "<h2>⚙️ Environment Configuration</h2>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr><th>Variable</th><th>Value</th><th>Status</th></tr>";

$importantVars = [
    'APP_ENV',
    'APP_DEBUG',
    'MONGODB_URI',
    'DB_HOST',
    'DB_DATABASE',
    'DB_USERNAME',
    'MAIL_HOST',
    'MAIL_PORT',
    'MAIL_USERNAME',
    'MAIL_ENCRYPTION',
    'JWT_SECRET',
    'CORS_ALLOWED_ORIGINS'
];

foreach ($importantVars as $var) {
    $value = getenv($var);
    $status = $value ? '✅ Set' : '❌ Missing';
    $displayValue = $value ? (in_array($var, ['MAIL_PASSWORD', 'JWT_SECRET', 'DB_PASSWORD', 'MONGODB_URI']) ?
        '••••••••' : htmlspecialchars($value)) : 'Not set';

    echo "<tr>
            <td><strong>$var</strong></td>
            <td>$displayValue</td>
            <td>$status</td>
          </tr>";
}

echo "</table>";

// Show all environment variables in debug mode
if (getenv('APP_DEBUG') === 'true' || isset($_GET['show_all'])) {
    echo "<h2>🔍 All Environment Variables</h2>";
    echo "<pre style='background: #f4f4f4; padding: 15px; overflow: auto;'>";
    $allVars = getenv();
    foreach ($allVars as $key => $value) {
        if (in_array($key, ['MAIL_PASSWORD', 'DB_PASSWORD', 'JWT_SECRET', 'MONGODB_URI'])) {
            echo htmlspecialchars($key) . " = ••••••••\n";
        } else {
            echo htmlspecialchars($key) . " = " . htmlspecialchars($value) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p>🔗 <a href='test.php?show_all=1'>Show all environment variables</a></p>";
}

// Health check endpoint for Render
if (isset($_GET['health'])) {
    header('Content-Type: application/json');

    // Check MongoDB connection for health status
    $mongoHealthy = false;
    if (getenv('MONGODB_URI') || (getenv('DB_HOST') && getenv('DB_DATABASE'))) {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';

            if (getenv('MONGODB_URI')) {
                $mongo = new MongoDB\Client(getenv('MONGODB_URI'));
            } else {
                $mongo = new MongoDB\Client(
                    'mongodb://' .
                    getenv('DB_USERNAME') . ':' .
                    getenv('DB_PASSWORD') . '@' .
                    getenv('DB_HOST') . ':' .
                    getenv('DB_PORT') . '/' .
                    getenv('DB_DATABASE')
                );
            }

            $mongo->listDatabases();
            $mongoHealthy = true;
        } catch (Exception $e) {
            $mongoHealthy = false;
        }
    }

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

echo "<hr>";
echo "<p><strong>Health Check:</strong> <a href='test.php?health=1'>test.php?health=1</a> (for Render monitoring)</p>";
echo "<p><strong>Debug Mode:</strong> " . (getenv('APP_DEBUG') === 'true' ? 'ON' : 'OFF') . "</p>";
?>
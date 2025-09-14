<?php
// public/test.php - Comprehensive test file for PHP backend

echo "<h1>🚀 PHP Backend Deployment Test</h1>";
echo "<p>Server Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";

require_once __DIR__ . '/../vendor/autoload.php';

// --- MongoDB Connection Test ---
echo "<h2>📊 MongoDB Connection Test</h2>";

$mongoHealthy = false;

if (getenv('MONGODB_URI') || (getenv('DB_HOST') && getenv('DB_DATABASE'))) {
    try {
        if (getenv('MONGODB_URI')) {
            $mongo = new MongoDB\Client(getenv('MONGODB_URI'));
            $mongoUriMsg = "MongoDB URI connection: CONFIGURED";
        } else {
            $mongo = new MongoDB\Client(
                sprintf(
                    'mongodb://%s:%s@%s:%s/%s',
                    getenv('DB_USERNAME'),
                    getenv('DB_PASSWORD'),
                    getenv('DB_HOST'),
                    getenv('DB_PORT') ?: 27017,
                    getenv('DB_DATABASE')
                )
            );
            $mongoUriMsg = "MongoDB component connection: CONFIGURED";
        }

        $mongo->listDatabases();
        $mongoHealthy = true;

        // Get database name
        $dbName = getenv('DB_DATABASE') ?: (preg_match('/\/([^?\/]+)(\?|$)/', getenv('MONGODB_URI'), $matches) ? $matches[1] : 'unknown');

        echo "<p style='color: green;'>✅ $mongoUriMsg</p>";
        echo "<p style='color: green;'>✅ MongoDB connection: SUCCESS</p>";
        echo "<p style='color: green;'>✅ Connected to database: " . htmlspecialchars($dbName) . "</p>";

    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ MongoDB connection: FAILED - " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color: orange;'>ℹ️ MongoDB: No credentials configured</p>";
}

// --- Email Service Test ---
echo "<h2>📧 Email Service Test</h2>";

$emailVars = ['MAIL_HOST','MAIL_PORT','MAIL_USERNAME','MAIL_PASSWORD'];
$missingVars = array_filter($emailVars, fn($var) => !getenv($var));

if (!empty($missingVars)) {
    echo "<p style='color: orange;'>ℹ️ Email: Missing environment variables: " . implode(', ', $missingVars) . "</p>";
} else {
    echo "<p style='color: green;'>✅ Email: All environment variables are set</p>";

    try {
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            throw new Exception("PHPMailer not installed. Run: composer require phpmailer/phpmailer");
        }

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = getenv('MAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('MAIL_USERNAME');
        $mail->Password = getenv('MAIL_PASSWORD');
        $mail->SMTPSecure = getenv('MAIL_ENCRYPTION') ?: 'tls';
        $mail->Port = (int)getenv('MAIL_PORT');

        if ($mail->smtpConnect()) {
            echo "<p style='color: green;'>✅ SMTP Connection: SUCCESS</p>";
            $mail->smtpClose();
        } else {
            echo "<p style='color: red;'>❌ SMTP Connection: FAILED</p>";
        }

        if (isset($_GET['send_test_email'])) {
            $mail->setFrom(getenv('MAIL_FROM_ADDRESS') ?: getenv('MAIL_USERNAME'), getenv('MAIL_FROM_NAME') ?: 'Test System');
            $mail->addAddress(getenv('MAIL_USERNAME'), 'Test Recipient');
            $mail->isHTML(true);
            $mail->Subject = 'Test Email from PHP Backend - ' . date('Y-m-d H:i:s');
            $mail->Body = '<h1>Test Email</h1><p>This is a test email from your PHP backend on Render.</p>';
            $mail->AltBody = 'This is a test email from your PHP backend on Render.';

            if ($mail->send()) {
                echo "<p style='color: green;'>✅ Test Email: SENT SUCCESSFULLY</p>";
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

// --- Environment Overview ---
echo "<h2>⚙️ Environment Configuration</h2>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr><th>Variable</th><th>Value</th><th>Status</th></tr>";

$importantVars = [
    'APP_ENV','APP_DEBUG','MONGODB_URI','DB_HOST','DB_DATABASE',
    'DB_USERNAME','MAIL_HOST','MAIL_PORT','MAIL_USERNAME','MAIL_ENCRYPTION',
    'JWT_SECRET','CORS_ALLOWED_ORIGINS'
];

foreach ($importantVars as $var) {
    $value = getenv($var);
    $status = $value ? '✅ Set' : '❌ Missing';
    $displayValue = $value && in_array($var, ['MAIL_PASSWORD','JWT_SECRET','DB_PASSWORD','MONGODB_URI']) ? '••••••••' : htmlspecialchars($value ?: 'Not set');
    echo "<tr><td><strong>$var</strong></td><td>$displayValue</td><td>$status</td></tr>";
}

echo "</table>";

// Debug mode: show all environment variables
if (getenv('APP_DEBUG') === 'true' || isset($_GET['show_all'])) {
    echo "<h2>🔍 All Environment Variables</h2>";
    echo "<pre style='background:#f4f4f4;padding:15px;overflow:auto;'>";
    foreach (getenv() as $key => $value) {
        $value = in_array($key,['MAIL_PASSWORD','DB_PASSWORD','JWT_SECRET','MONGODB_URI']) ? '••••••••' : $value;
        echo htmlspecialchars($key) . " = " . htmlspecialchars($value) . "\n";
    }
    echo "</pre>";
} else {
    echo "<p>🔗 <a href='test.php?show_all=1'>Show all environment variables</a></p>";
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

echo "<hr>";
echo "<p><strong>Health Check:</strong> <a href='test.php?health=1'>test.php?health=1</a></p>";
echo "<p><strong>Debug Mode:</strong> " . (getenv('APP_DEBUG') === 'true' ? 'ON' : 'OFF') . "</p>";
?>
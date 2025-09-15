<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Services\DatabaseService;

try {
    $service = App\Services\DatabaseService::getInstance();
} catch (Exception $e) {
    error_log("Migration: DB init failed: " . $e->getMessage());
    // choose to exit non-zero or return gracefully; here we exit but not crash the container startup
    exit(0);
}

$tables = [
    'customers' => [
        'create' => "CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            ssn VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE (email),
            UNIQUE (ssn)
        )",
    ],
    'departments' => [
        'create' => "CREATE TABLE IF NOT EXISTS departments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE (name)
        )",
    ],
    'bank_employees' => [
        'create' => "CREATE TABLE IF NOT EXISTS bank_employees (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            department_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE (email),
            FOREIGN KEY (department_id) REFERENCES departments(id)
        )",
    ],
    'loan_products' => [
        'create' => "CREATE TABLE IF NOT EXISTS loan_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (product_name)
        )",
    ],
    'loan_applications' => [
        'create' => "CREATE TABLE IF NOT EXISTS loan_applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_reference VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE (application_reference)
        )",
    ],
    'collaterals' => [
        'create' => "CREATE TABLE IF NOT EXISTS collaterals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (customer_id),
            FOREIGN KEY (customer_id) REFERENCES customers(id)
        )",
    ],
    'loans' => [
        'create' => "CREATE TABLE IF NOT EXISTS loans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (application_id),
            FOREIGN KEY (application_id) REFERENCES loan_applications(id)
        )",
    ],
    'documents' => [
        'create' => "CREATE TABLE IF NOT EXISTS documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (customer_id),
            FOREIGN KEY (customer_id) REFERENCES customers(id)
        )",
    ],
    'payment_schedules' => [
        'create' => "CREATE TABLE IF NOT EXISTS payment_schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            loan_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (loan_id),
            FOREIGN KEY (loan_id) REFERENCES loans(id)
        )",
    ],
    'payment_transactions' => [
        'create' => "CREATE TABLE IF NOT EXISTS payment_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            loan_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (loan_id),
            FOREIGN KEY (loan_id) REFERENCES loans(id)
        )",
    ],
    'notifications' => [
        'create' => "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipient_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (recipient_id)
        )",
    ],
    'audit_logs' => [
        'create' => "CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (user_id)
        )",
    ],
    'refresh_tokens' => [
        'create' => "CREATE TABLE IF NOT EXISTS refresh_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE (token)
        )",
    ],
    'system_settings' => [
        'create' => "CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            test_type VARCHAR(50),
            timestamp INT,
            random VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
    ],
];

foreach ($tables as $name => $table) {
    echo "Ensuring table: $name\n";
    try {
        $pdo->exec($table['create']);
        echo " ✓ $name OK\n";
    } catch (Exception $e) {
        echo " ! $name: " . $e->getMessage() . "\n";
    }
}

// Seed departments if absent
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM departments");
    $count = $stmt->fetchColumn();
    if ($count === 0) {
        $pdo->exec("INSERT INTO departments (name, description, created_at, updated_at) VALUES (
            'Loan Department',
            'Handles all loan-related operations',
            NOW(),
            NOW()
        )");
        echo "Seeded departments\n";
    }
} catch (Exception $e) {
    echo " ! Seeding departments failed: " . $e->getMessage() . "\n";
}

echo "Migrations finished.\n";
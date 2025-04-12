<?php
// File: migrations.php

// Autoload dependencies and load environment variables via your config
require_once __DIR__ . '/vendor/autoload.php';

// Get PDO connection from our existing database config
$pdo = require __DIR__ . '/config/database.php';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass);

    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");

    echo "Connected successfully to MySQL server <br>";
    echo "Database '$dbName' selected or created successfully <br>";

    // SQL statements array
    $sqlStatements = [
        // Customers table
        "CREATE TABLE IF NOT EXISTS customers (
            customer_id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            date_of_birth DATE NOT NULL,
            password VARCHAR(255) NOT NULL,
            address TEXT NOT NULL,
            city VARCHAR(50) NOT NULL,
            state VARCHAR(50) NOT NULL,
            postal_code VARCHAR(20) NOT NULL,
            country VARCHAR(50) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            ssn VARCHAR(20) NOT NULL UNIQUE,
            income DECIMAL(30, 2),
            employment_status VARCHAR(50),
            occupation VARCHAR(50),
            employer VARCHAR(50),
            account_number VARCHAR(50) UNIQUE,
            bank VARCHAR(50),
            credit_score INT,
            id_verification_status VARCHAR(20) DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",

        // Departments table
        "CREATE TABLE IF NOT EXISTS departments (
            department_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",

        // Bank Employees table
        "CREATE TABLE IF NOT EXISTS bank_employees (
            employee_id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            last_login TIMESTAMP NULL,
            department_id INT NOT NULL,
            role ENUM('customer', 'loan_officer', 'admin', 'manager') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE CASCADE
        )",

        // Loan Products table
        "CREATE TABLE IF NOT EXISTS loan_products (
            product_id INT AUTO_INCREMENT PRIMARY KEY,
            product_name VARCHAR(100) NOT NULL,
            description TEXT,
            interest_rate DECIMAL(5, 2) NOT NULL,
            min_term INT NOT NULL,
            max_term INT NOT NULL,
            min_amount DECIMAL(12, 2) NOT NULL,
            max_amount DECIMAL(12, 2) NOT NULL,
            requires_collateral BOOLEAN DEFAULT FALSE,
            early_payment_fee DECIMAL(5, 2) DEFAULT 0,
            late_payment_fee DECIMAL(5, 2) DEFAULT 0,
            eligibility_criteria TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",

        // Loan Applications table
        "CREATE TABLE IF NOT EXISTS loan_applications (
            application_id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            product_id INT NOT NULL,
            requested_amount DECIMAL(12, 2) NOT NULL,
            requested_term INT NOT NULL,
            purpose VARCHAR(100) NOT NULL,
            application_reference VARCHAR(100) NOT NULL UNIQUE,
            application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('submitted', 'under_review', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'submitted',
            reviewed_by INT NULL,
            review_date TIMESTAMP NULL,
            review_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES loan_products(product_id),
            FOREIGN KEY (reviewed_by) REFERENCES bank_employees(employee_id) ON DELETE SET NULL
        )",

        // Collaterals table
        "CREATE TABLE IF NOT EXISTS collaterals (
            collateral_id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            collateral_type VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            estimated_value DECIMAL(12, 2) NOT NULL,
            verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
            verified_by INT NULL,
            verification_date TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
            FOREIGN KEY (verified_by) REFERENCES bank_employees(employee_id) ON DELETE SET NULL
        )",

        // Loans table
        "CREATE TABLE IF NOT EXISTS loans (
            loan_id INT AUTO_INCREMENT PRIMARY KEY,
            application_id INT NOT NULL,
            customer_id INT NOT NULL,
            product_id INT NOT NULL,
            principal_amount DECIMAL(12, 2) NOT NULL,
            interest_rate DECIMAL(5, 2) NOT NULL,
            term INT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            status ENUM('active', 'paid', 'defaulted', 'cancelled') NOT NULL DEFAULT 'active',
            collateral_id INT NULL,
            approved_by INT NOT NULL,
            approval_date TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (application_id) REFERENCES loan_applications(application_id),
            FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES loan_products(product_id),
            FOREIGN KEY (collateral_id) REFERENCES collaterals(collateral_id) ON DELETE SET NULL,
            FOREIGN KEY (approved_by) REFERENCES bank_employees(employee_id)
        )",

        // Customer Loans table
        "CREATE OR REPLACE VIEW customer_loans AS
            SELECT 
                la.application_reference AS id,
                lp.product_name AS name,
                -- Use the approved loan's principal if available; otherwise, use the requested amount
                CONCAT('₦', FORMAT(COALESCE(l.principal_amount, la.requested_amount), 0)) AS amount,
                CONCAT('₦', FORMAT(IFNULL(SUM(pt.amount_paid), 0), 0)) AS amountPaid,
                -- For dueDate, show the loan's due date if approved, or a placeholder if not
                CASE 
                WHEN l.loan_id IS NOT NULL THEN 
                    DATE_FORMAT(
                    CASE 
                        WHEN MAX(ps.due_date) IS NOT NULL THEN MAX(ps.due_date)
                        ELSE DATE_ADD(l.start_date, INTERVAL l.term MONTH)
                    END, '%M %d, %Y'
                    )
                ELSE 'Pending'
                END AS dueDate,
                -- Calculate nextPayment only if a loan exists; otherwise set as N/A or 0
                CASE 
                WHEN l.loan_id IS NOT NULL THEN 
                    CONCAT('₦', FORMAT(
                        CASE
                            WHEN (
                                SELECT ps2.total_amount
                                FROM payment_schedules ps2
                                WHERE ps2.loan_id = l.loan_id AND ps2.status = 'pending'
                                ORDER BY ps2.due_date ASC
                                LIMIT 1
                            ) IS NOT NULL THEN (
                                SELECT ps2.total_amount
                                FROM payment_schedules ps2
                                WHERE ps2.loan_id = l.loan_id AND ps2.status = 'pending'
                                ORDER BY ps2.due_date ASC
                                LIMIT 1
                            )
                            ELSE (l.principal_amount + (l.principal_amount * (l.interest_rate / 100) * (l.term / 12))) / l.term
                        END, 0))
                ELSE 'N/A'
                END AS nextPayment,
                ROUND(
                    IFNULL(SUM(pt.amount_paid) / COALESCE(l.principal_amount, la.requested_amount) * 100, 0)
                ) AS progress,
                -- Determine the status: if the loan is not created yet then use the application status, 
                -- but if it is approved then show the approved status for example.
                CASE 
                WHEN l.loan_id IS NULL THEN la.status
                WHEN la.status = 'under_review' THEN 'pending'
                ELSE la.status
                END AS status,
                -- Use the loan start date if available, otherwise the application submission date
                DATE_FORMAT(COALESCE(l.start_date, la.application_date), '%M %d, %Y') AS date,
                la.customer_id
            FROM loan_applications la
            LEFT JOIN loans l ON la.application_id = l.application_id
            LEFT JOIN loan_products lp ON la.product_id = lp.product_id
            LEFT JOIN payment_schedules ps ON l.loan_id = ps.loan_id
            LEFT JOIN payment_transactions pt ON l.loan_id = pt.loan_id AND pt.status = 'completed'
            GROUP BY 
                la.application_reference,
                lp.product_name,
                COALESCE(l.principal_amount, la.requested_amount),
                l.loan_id,
                la.status,
                COALESCE(l.start_date, la.application_date),
                la.customer_id;
        ",

        // Documents table
        "CREATE TABLE IF NOT EXISTS documents (
            document_id INT AUTO_INCREMENT PRIMARY KEY,
            document_type VARCHAR(50) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            file_name VARCHAR(100) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
            verified_by INT NULL,
            verification_date TIMESTAMP NULL,
            customer_id INT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
            FOREIGN KEY (verified_by) REFERENCES bank_employees(employee_id) ON DELETE SET NULL
        )",

        // Payment Schedules table
        "CREATE TABLE IF NOT EXISTS payment_schedules (
            schedule_id INT AUTO_INCREMENT PRIMARY KEY,
            loan_id INT NOT NULL,
            due_date DATE NOT NULL,
            installment_number INT NOT NULL,
            principal_amount DECIMAL(12, 2) NOT NULL,
            interest_amount DECIMAL(12, 2) NOT NULL,
            total_amount DECIMAL(12, 2) NOT NULL,
            remaining_balance DECIMAL(12, 2) NOT NULL,
            status ENUM('pending', 'paid', 'partial', 'overdue', 'defaulted') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (loan_id) REFERENCES loans(loan_id) ON DELETE CASCADE
        )",

        // Payment Transactions table
        "CREATE TABLE IF NOT EXISTS payment_transactions (
            transaction_id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,        
            loan_id INT NOT NULL,
            schedule_id INT NOT NULL,
            amount_paid DECIMAL(12, 2) NOT NULL,
            principal_portion DECIMAL(12, 2) NOT NULL,
            interest_portion DECIMAL(12, 2) NOT NULL,
            payment_date TIMESTAMP NOT NULL,
            payment_method ENUM('online', 'bank_transfer', 'cash', 'check', 'other') NOT NULL,
            transaction_reference VARCHAR(100),
            processed_by INT NULL,
            status ENUM('pending', 'completed', 'failed', 'reversed') NOT NULL DEFAULT 'pending',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE, 
            FOREIGN KEY (loan_id) REFERENCES loans(loan_id) ON DELETE CASCADE,
            FOREIGN KEY (schedule_id) REFERENCES payment_schedules(schedule_id) ON DELETE CASCADE,
            FOREIGN KEY (processed_by) REFERENCES bank_employees(employee_id) ON DELETE SET NULL
        )",

        // Notifications table
        "CREATE TABLE IF NOT EXISTS notifications (
            notification_id INT AUTO_INCREMENT PRIMARY KEY,
            recipient_type ENUM('customer', 'employee') NOT NULL,
            recipient_id INT NOT NULL,
            notification_type ENUM('email', 'sms', 'in_app') NOT NULL,
            subject VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            related_to ENUM('application', 'loan', 'payment', 'document', 'general') NOT NULL,
            related_id INT NULL,
            status ENUM('pending', 'sent', 'failed', 'read') NOT NULL DEFAULT 'pending',
            sent_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",

        // Audit Logs table
        "CREATE TABLE IF NOT EXISTS audit_logs (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            user_type ENUM('customer', 'employee', 'system') NOT NULL,
            action VARCHAR(100) NOT NULL,
            entity_type VARCHAR(50) NOT NULL,
            entity_id INT NOT NULL,
            old_values TEXT NULL,
            new_values TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        // Refresh tokens table
        "CREATE TABLE IF NOT EXISTS refresh_tokens (
            token_id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            token VARCHAR(255) NOT NULL UNIQUE,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE
        )"
    ];

    // Execute each SQL statement
    foreach ($sqlStatements as $index => $sql) {
        try {
            $pdo->exec($sql);
            echo "Table " . ($index + 1) . " created successfully \n";
        } catch (PDOException $e) {
            echo "Error creating table " . ($index + 1) . ": " . $e->getMessage() . " <br>";
        }
    }

    echo "\n All tables have been created successfully!";

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
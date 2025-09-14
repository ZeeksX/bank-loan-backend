<?php
// File: migrations.php
// MongoDB migration script for Docker deployment on Render

require_once __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\UTCDateTime;

// Load environment variables
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
}

// Get MongoDB connection string
$mongoUri = getenv('MONGODB_URI') ?: 'mongodb://localhost:27017/bank_loan_db';
$dbName = getenv('DB_DATABASE') ?: 'bank_loan_db';

try {
    // Connect to MongoDB
    $mongo = new Client($mongoUri);
    $db = $mongo->selectDatabase($dbName);
    echo "Connected to MongoDB database '$dbName' successfully\n";

    // Define collections and indexes
    $collections = [
        'customers' => [
            'indexes' => [
                ['key' => ['email' => 1], 'unique' => true],
                ['key' => ['ssn' => 1], 'unique' => true],
                ['key' => ['account_number' => 1], 'unique' => true],
            ],
        ],
        'departments' => [
            'indexes' => [
                ['key' => ['name' => 1], 'unique' => true],
            ],
        ],
        'bank_employees' => [
            'indexes' => [
                ['key' => ['email' => 1], 'unique' => true],
                ['key' => ['department_id' => 1]],
            ],
        ],
        'loan_products' => [
            'indexes' => [
                ['key' => ['product_name' => 1]],
            ],
        ],
        'loan_applications' => [
            'indexes' => [
                ['key' => ['application_reference' => 1], 'unique' => true],
                ['key' => ['customer_id' => 1]],
                ['key' => ['product_id' => 1]],
            ],
        ],
        'collaterals' => [
            'indexes' => [
                ['key' => ['customer_id' => 1]],
            ],
        ],
        'loans' => [
            'indexes' => [
                ['key' => ['application_id' => 1]],
                ['key' => ['customer_id' => 1]],
                ['key' => ['product_id' => 1]],
            ],
        ],
        'documents' => [
            'indexes' => [
                ['key' => ['customer_id' => 1]],
            ],
        ],
        'payment_schedules' => [
            'indexes' => [
                ['key' => ['loan_id' => 1]],
                ['key' => ['due_date' => 1]],
            ],
        ],
        'payment_transactions' => [
            'indexes' => [
                ['key' => ['loan_id' => 1]],
                ['key' => ['schedule_id' => 1]],
                ['key' => ['customer_id' => 1]],
            ],
        ],
        'notifications' => [
            'indexes' => [
                ['key' => ['recipient_id' => 1]],
                ['key' => ['related_id' => 1]],
            ],
        ],
        'audit_logs' => [
            'indexes' => [
                ['key' => ['user_id' => 1]],
                ['key' => ['entity_id' => 1]],
            ],
        ],
        'refresh_tokens' => [
            'indexes' => [
                ['key' => ['token' => 1], 'unique' => true],
                ['key' => ['customer_id' => 1]],
            ],
        ],
    ];

    // Create collections and indexes
    foreach ($collections as $collectionName => $config) {
        try {
            // Create collection (MongoDB creates collections automatically on first insert, but we ensure it exists)
            $db->createCollection($collectionName);
            echo "Collection '$collectionName' created successfully\n";

            // Create indexes
            foreach ($config['indexes'] as $index) {
                $db->$collectionName->createIndex($index['key'], array_filter(['unique' => $index['unique'] ?? false]));
                echo "Index on '$collectionName' for fields " . json_encode($index['key']) . " created successfully\n";
            }
        } catch (Exception $e) {
            echo "Error creating collection or index for '$collectionName': " . $e->getMessage() . "\n";
        }
    }

    // Seed initial data (optional)
    try {
        $departments = $db->departments;
        if ($departments->countDocuments([]) === 0) {
            $departments->insertOne([
                'name' => 'Loan Department',
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime(),
            ]);
            echo "Seeded initial department data\n";
        }
    } catch (Exception $e) {
        echo "Error seeding data: " . $e->getMessage() . "\n";
    }

    echo "MongoDB migration completed successfully!\n";

} catch (Exception $e) {
    die("MongoDB connection failed: " . $e->getMessage());
}
?>
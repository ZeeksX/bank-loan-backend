<?php
// File: migrations.php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Services/DatabaseService.php';

// Load environment variables
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    try {
        Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
    } catch (Exception $e) {
        echo "Warning: Could not load .env file: " . $e->getMessage() . "\n";
    }
}

try {
    // Get database service instance
    $db = App\Services\DatabaseService::getInstance();
    $clientType = $db->getClientType();

    echo "Connected to MongoDB successfully using '$clientType' client\n";
    echo "Database: " . getDatabaseName() . "\n";
    echo "===========================================\n";

    // Define collections with their indexes and seed data
    $collections = [
        'customers' => [
            'indexes' => [
                ['key' => ['email' => 1], 'unique' => true],
                ['key' => ['ssn' => 1], 'unique' => true],
                ['key' => ['account_number' => 1], 'unique' => true],
                ['key' => ['phone' => 1]],
                ['key' => ['created_at' => 1]],
            ],
            'seed' => []
        ],
        'departments' => [
            'indexes' => [
                ['key' => ['name' => 1], 'unique' => true],
            ],
            'seed' => [
                [
                    'name' => 'Loan Department',
                    'description' => 'Handles all loan-related operations',
                    'created_at' => time(),
                    'updated_at' => time()
                ],
                [
                    'name' => 'Customer Service',
                    'description' => 'Customer support and relations',
                    'created_at' => time(),
                    'updated_at' => time()
                ],
                [
                    'name' => 'Risk Management',
                    'description' => 'Risk assessment and management',
                    'created_at' => time(),
                    'updated_at' => time()
                ]
            ]
        ],
        'bank_employees' => [
            'indexes' => [
                ['key' => ['email' => 1], 'unique' => true],
                ['key' => ['employee_id' => 1], 'unique' => true],
                ['key' => ['department_id' => 1]],
                ['key' => ['role' => 1]],
            ],
            'seed' => []
        ],
        'loan_products' => [
            'indexes' => [
                ['key' => ['product_name' => 1]],
                ['key' => ['product_type' => 1]],
                ['key' => ['is_active' => 1]],
            ],
            'seed' => [
                [
                    'product_name' => 'Personal Loan',
                    'product_type' => 'personal',
                    'description' => 'Unsecured personal loans for various purposes',
                    'min_amount' => 1000,
                    'max_amount' => 50000,
                    'min_term_months' => 12,
                    'max_term_months' => 60,
                    'interest_rate_min' => 8.5,
                    'interest_rate_max' => 15.0,
                    'processing_fee_percentage' => 2.0,
                    'is_active' => true,
                    'created_at' => time(),
                    'updated_at' => time()
                ],
                [
                    'product_name' => 'Home Loan',
                    'product_type' => 'mortgage',
                    'description' => 'Secured home loans with competitive rates',
                    'min_amount' => 50000,
                    'max_amount' => 1000000,
                    'min_term_months' => 60,
                    'max_term_months' => 360,
                    'interest_rate_min' => 6.5,
                    'interest_rate_max' => 10.0,
                    'processing_fee_percentage' => 1.0,
                    'is_active' => true,
                    'created_at' => time(),
                    'updated_at' => time()
                ],
                [
                    'product_name' => 'Car Loan',
                    'product_type' => 'auto',
                    'description' => 'Auto loans for new and used vehicles',
                    'min_amount' => 10000,
                    'max_amount' => 100000,
                    'min_term_months' => 24,
                    'max_term_months' => 84,
                    'interest_rate_min' => 7.0,
                    'interest_rate_max' => 12.0,
                    'processing_fee_percentage' => 1.5,
                    'is_active' => true,
                    'created_at' => time(),
                    'updated_at' => time()
                ]
            ]
        ],
        'loan_applications' => [
            'indexes' => [
                ['key' => ['application_reference' => 1], 'unique' => true],
                ['key' => ['customer_id' => 1]],
                ['key' => ['product_id' => 1]],
                ['key' => ['status' => 1]],
                ['key' => ['application_date' => 1]],
                ['key' => ['assigned_officer_id' => 1]],
            ],
            'seed' => []
        ],
        'collaterals' => [
            'indexes' => [
                ['key' => ['customer_id' => 1]],
                ['key' => ['collateral_type' => 1]],
                ['key' => ['application_id' => 1]],
            ],
            'seed' => []
        ],
        'loans' => [
            'indexes' => [
                ['key' => ['application_id' => 1]],
                ['key' => ['customer_id' => 1]],
                ['key' => ['product_id' => 1]],
                ['key' => ['loan_reference' => 1], 'unique' => true],
                ['key' => ['status' => 1]],
                ['key' => ['disbursement_date' => 1]],
            ],
            'seed' => []
        ],
        'documents' => [
            'indexes' => [
                ['key' => ['customer_id' => 1]],
                ['key' => ['application_id' => 1]],
                ['key' => ['document_type' => 1]],
                ['key' => ['upload_date' => 1]],
            ],
            'seed' => []
        ],
        'payment_schedules' => [
            'indexes' => [
                ['key' => ['loan_id' => 1]],
                ['key' => ['due_date' => 1]],
                ['key' => ['status' => 1]],
                ['key' => ['installment_number' => 1]],
            ],
            'seed' => []
        ],
        'payment_transactions' => [
            'indexes' => [
                ['key' => ['loan_id' => 1]],
                ['key' => ['schedule_id' => 1]],
                ['key' => ['customer_id' => 1]],
                ['key' => ['payment_date' => 1]],
                ['key' => ['transaction_reference' => 1], 'unique' => true],
                ['key' => ['payment_method' => 1]],
            ],
            'seed' => []
        ],
        'notifications' => [
            'indexes' => [
                ['key' => ['recipient_id' => 1]],
                ['key' => ['recipient_type' => 1]],
                ['key' => ['related_id' => 1]],
                ['key' => ['notification_type' => 1]],
                ['key' => ['created_at' => 1]],
                ['key' => ['is_read' => 1]],
            ],
            'seed' => []
        ],
        'audit_logs' => [
            'indexes' => [
                ['key' => ['user_id' => 1]],
                ['key' => ['user_type' => 1]],
                ['key' => ['entity_type' => 1]],
                ['key' => ['entity_id' => 1]],
                ['key' => ['action' => 1]],
                ['key' => ['created_at' => 1]],
            ],
            'seed' => []
        ],
        'refresh_tokens' => [
            'indexes' => [
                ['key' => ['token' => 1], 'unique' => true],
                ['key' => ['customer_id' => 1]],
                ['key' => ['expires_at' => 1]],
                ['key' => ['created_at' => 1]],
            ],
            'seed' => []
        ],
        'system_settings' => [
            'indexes' => [
                ['key' => ['setting_key' => 1], 'unique' => true],
                ['key' => ['category' => 1]],
            ],
            'seed' => [
                [
                    'setting_key' => 'system_version',
                    'setting_value' => '1.0.0',
                    'category' => 'system',
                    'description' => 'Current system version',
                    'created_at' => time(),
                    'updated_at' => time()
                ],
                [
                    'setting_key' => 'max_loan_amount',
                    'setting_value' => '1000000',
                    'category' => 'loan_limits',
                    'description' => 'Maximum loan amount allowed',
                    'created_at' => time(),
                    'updated_at' => time()
                ],
                [
                    'setting_key' => 'default_interest_rate',
                    'setting_value' => '10.5',
                    'category' => 'loan_settings',
                    'description' => 'Default interest rate for loans',
                    'created_at' => time(),
                    'updated_at' => time()
                ]
            ]
        ]
    ];

    $totalCollections = count($collections);
    $processedCollections = 0;
    $errors = [];

    // Create collections, indexes, and seed data
    foreach ($collections as $collectionName => $config) {
        echo "\n--- Processing Collection: $collectionName ---\n";

        try {
            // Create collection (this is automatic in most MongoDB setups)
            $db->createCollection($collectionName);
            echo "✓ Collection '$collectionName' ensured\n";

            // Create indexes
            $indexCount = 0;
            foreach ($config['indexes'] as $index) {
                try {
                    $options = [];
                    if (isset($index['unique']) && $index['unique']) {
                        $options['unique'] = true;
                    }

                    $db->createIndex($collectionName, $index['key'], $options);
                    $indexCount++;
                } catch (Exception $e) {
                    // Index might already exist, which is fine
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        $errors[] = "Index creation failed for $collectionName: " . $e->getMessage();
                    }
                }
            }
            echo "✓ $indexCount indexes processed for '$collectionName'\n";

            // Seed data (only if collection is empty)
            if (!empty($config['seed'])) {
                $existingCount = $db->count($collectionName);
                if ($existingCount === 0) {
                    $seedResult = $db->insertMany($collectionName, $config['seed']);
                    echo "✓ Seeded " . $seedResult['insertedCount'] . " documents in '$collectionName'\n";
                } else {
                    echo "- Collection '$collectionName' already has $existingCount documents, skipping seed\n";
                }
            }

            $processedCollections++;

        } catch (Exception $e) {
            $errors[] = "Collection setup failed for $collectionName: " . $e->getMessage();
            echo "✗ Error processing '$collectionName': " . $e->getMessage() . "\n";
        }
    }

    echo "\n===========================================\n";
    echo "Migration Summary:\n";
    echo "✓ Successfully processed: $processedCollections/$totalCollections collections\n";

    if (!empty($errors)) {
        echo "⚠ Errors encountered:\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }

    // Test database functionality
    echo "\n--- Database Functionality Test ---\n";

    try {
        // Test basic operations
        echo "Testing ping... ";
        if ($db->ping()) {
            echo "✓ PASS\n";
        } else {
            echo "✗ FAIL\n";
        }

        // Test insert operation
        echo "Testing insert operation... ";
        $testDoc = [
            'test_field' => 'test_value_' . uniqid(),
            'created_at' => time(),
            'updated_at' => time()
        ];

        $insertResult = $db->insertOne('system_settings', $testDoc);
        if ($insertResult['insertedCount'] === 1) {
            echo "✓ PASS\n";

            // Test find operation
            echo "Testing find operation... ";
            $foundDoc = $db->findOne('system_settings', ['test_field' => $testDoc['test_field']]);
            if ($foundDoc && $foundDoc['test_field'] === $testDoc['test_field']) {
                echo "✓ PASS\n";

                // Test update operation
                echo "Testing update operation... ";
                $updateResult = $db->updateOne(
                    'system_settings',
                    ['test_field' => $testDoc['test_field']],
                    ['$set' => ['test_field_updated' => true]]
                );
                if ($updateResult['modifiedCount'] === 1) {
                    echo "✓ PASS\n";

                    // Test delete operation
                    echo "Testing delete operation... ";
                    $deleteResult = $db->deleteOne('system_settings', ['test_field' => $testDoc['test_field']]);
                    if ($deleteResult['deletedCount'] === 1) {
                        echo "✓ PASS\n";
                    } else {
                        echo "✗ FAIL (delete)\n";
                    }
                } else {
                    echo "✗ FAIL (update)\n";
                }
            } else {
                echo "✗ FAIL (find)\n";
            }
        } else {
            echo "✗ FAIL (insert)\n";
        }

    } catch (Exception $e) {
        echo "✗ FAIL - Test error: " . $e->getMessage() . "\n";
    }

    // Display database statistics
    echo "\n--- Database Statistics ---\n";
    $stats = $db->getStats();
    echo "Client Type: " . $stats['client_type'] . "\n";
    echo "Database Name: " . $stats['database_name'] . "\n";
    echo "Connection Status: " . ($stats['connected'] ? 'Connected' : 'Disconnected') . "\n";
    echo "Collections: " . count($stats['collections']) . "\n";

    if (!empty($stats['collections'])) {
        echo "Collection List:\n";
        foreach ($stats['collections'] as $col) {
            $count = 0;
            try {
                $count = $db->count($col);
            } catch (Exception $e) {
                // Count failed, skip
            }
            echo "  - $col ($count documents)\n";
        }
    }

    if (isset($stats['server_info'])) {
        echo "Server Version: " . $stats['server_info']['version'] . "\n";
        echo "Server Uptime: " . $stats['server_info']['uptime'] . " seconds\n";
    }

    // Performance test
    echo "\n--- Performance Test ---\n";
    echo "Running performance test with 100 documents...\n";

    $startTime = microtime(true);

    // Generate test documents
    $testDocs = [];
    for ($i = 0; $i < 100; $i++) {
        $testDocs[] = [
            'test_id' => $i,
            'test_data' => 'performance_test_' . uniqid(),
            'random_number' => rand(1, 1000),
            'created_at' => time(),
            'updated_at' => time()
        ];
    }

    try {
        // Bulk insert
        $insertTime = microtime(true);
        $bulkResult = $db->insertMany('system_settings', $testDocs);
        $insertDuration = microtime(true) - $insertTime;

        if ($bulkResult['insertedCount'] === 100) {
            echo "✓ Bulk insert: 100 documents in " . number_format($insertDuration * 1000, 2) . "ms\n";

            // Bulk find
            $findTime = microtime(true);
            $foundDocs = $db->find('system_settings', ['test_data' => ['$regex' => '^performance_test_']]);
            $findDuration = microtime(true) - $findTime;

            echo "✓ Bulk find: " . count($foundDocs) . " documents in " . number_format($findDuration * 1000, 2) . "ms\n";

            // Cleanup test documents
            $deleteTime = microtime(true);
            $deleteResult = $db->deleteMany('system_settings', ['test_data' => ['$regex' => '^performance_test_']]);
            $deleteDuration = microtime(true) - $deleteTime;

            echo "✓ Bulk delete: " . $deleteResult['deletedCount'] . " documents in " . number_format($deleteDuration * 1000, 2) . "ms\n";

        } else {
            echo "✗ Bulk insert failed\n";
        }
    } catch (Exception $e) {
        echo "✗ Performance test failed: " . $e->getMessage() . "\n";
    }

    $totalDuration = microtime(true) - $startTime;
    echo "Total performance test duration: " . number_format($totalDuration * 1000, 2) . "ms\n";

    echo "\n===========================================\n";
    echo "MongoDB migration completed successfully!\n";
    echo "Client Type: " . $clientType . "\n";
    echo "Database ready for use.\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
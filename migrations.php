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

class DatabaseMigrator {
    private $db;
    private $collections;

    public function __construct() {
        $this->db = App\Services\DatabaseService::getInstance();
        $this->initializeCollections();
    }

    private function initializeCollections(): void {
        $this->collections = [
            // Same $collections array as in the original script
            // ... (omitted for brevity, include the full $collections array here)
        ];
    }

    public function run(): void {
        try {
            echo "Connected to MongoDB successfully using '{$this->db->getClientType()}' client\n";
            echo "Database: " . $this->getDatabaseName() . "\n";
            echo "===========================================\n";

            $this->processCollections();
            $this->runFunctionalityTests();
            $this->runPerformanceTests();

            echo "\n===========================================\n";
            echo "MongoDB migration completed successfully!\n";
            echo "Client Type: " . $this->db->getClientType() . "\n";
            echo "Database ready for use.\n";
        } catch (Exception $e) {
            echo "Migration failed: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            exit(1);
        }
    }

    private function processCollections(): void {
        $totalCollections = count($this->collections);
        $processedCollections = 0;
        $errors = [];

        foreach ($this->collections as $collectionName => $config) {
            echo "\n--- Processing Collection: $collectionName ---\n";
            try {
                $this->db->createCollection($collectionName);
                echo "✓ Collection '$collectionName' ensured\n";

                $indexCount = $this->createIndexes($collectionName, $config['indexes'], $errors);
                echo "✓ $indexCount indexes processed for '$collectionName'\n";

                if (!empty($config['seed'])) {
                    $this->seedCollection($collectionName, $config['seed']);
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
    }

    private function createIndexes(string $collectionName, array $indexes, array &$errors): int {
        $indexCount = 0;
        foreach ($indexes as $index) {
            try {
                $options = isset($index['unique']) && $index['unique'] ? ['unique' => true] : [];
                $this->db->createIndex($collectionName, $index['key'], $options);
                $indexCount++;
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    $errors[] = "Index creation failed for $collectionName: " . $e->getMessage();
                }
            }
        }
        return $indexCount;
    }

    private function seedCollection(string $collectionName, array $seed): void {
        $existingCount = $this->db->count($collectionName);
        if ($existingCount === 0) {
            $seedResult = $this->db->insertMany($collectionName, $seed);
            echo "✓ Seeded " . $seedResult['insertedCount'] . " documents in '$collectionName'\n";
        } else {
            echo "- Collection '$collectionName' already has $existingCount documents, skipping seed\n";
        }
    }

    private function runFunctionalityTests(): void {
        echo "\n--- Database Functionality Test ---\n";
        try {
            echo "Testing ping... ";
            echo $this->db->ping() ? "✓ PASS\n" : "✗ FAIL\n";

            $testDoc = [
                'test_field' => 'test_value_' . uniqid(),
                'created_at' => time(),
                'updated_at' => time()
            ];

            echo "Testing insert operation... ";
            $insertResult = $this->db->insertOne('system_settings', $testDoc);
            if ($insertResult['insertedCount'] === 1) {
                echo "✓ PASS\n";

                echo "Testing find operation... ";
                $foundDoc = $this->db->findOne('system_settings', ['test_field' => $testDoc['test_field']]);
                if ($foundDoc && $foundDoc['test_field'] === $testDoc['test_field']) {
                    echo "✓ PASS\n";

                    echo "Testing update operation... ";
                    $updateResult = $this->db->updateOne(
                        'system_settings',
                        ['test_field' => $testDoc['test_field']],
                        ['$set' => ['test_field_updated' => true]]
                    );
                    if ($updateResult['modifiedCount'] === 1) {
                        echo "✓ PASS\n";

                        echo "Testing delete operation... ";
                        $deleteResult = $this->db->deleteOne('system_settings', ['test_field' => $testDoc['test_field']]);
                        echo $deleteResult['deletedCount'] === 1 ? "✓ PASS\n" : "✗ FAIL (delete)\n";
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
    }

    private function runPerformanceTests(): void {
        echo "\n--- Performance Test ---\n";
        echo "Running performance test with 100 documents...\n";

        $startTime = microtime(true);
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
            $insertTime = microtime(true);
            $bulkResult = $this->db->insertMany('system_settings', $testDocs);
            $insertDuration = microtime(true) - $insertTime;

            if ($bulkResult['insertedCount'] === 100) {
                echo "✓ Bulk insert: 100 documents in " . number_format($insertDuration * 1000, 2) . "ms\n";

                $findTime = microtime(true);
                $foundDocs = $this->db->find('system_settings', ['test_data' => ['$regex' => '^performance_test_']]);
                $findDuration = microtime(true) - $findTime;
                echo "✓ Bulk find: " . count($foundDocs) . " documents in " . number_format($findDuration * 1000, 2) . "ms\n";

                $deleteTime = microtime(true);
                $deleteResult = $this->db->deleteMany('system_settings', ['test_data' => ['$regex' => '^performance_test_']]);
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
    }

    private function getDatabaseName(): string {
        return 'bank_loan_db'; // Replace with actual method or configuration
    }
}

// Execute migration
$migrator = new DatabaseMigrator();
$migrator->run();
<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Services\DatabaseService;

$service = DatabaseService::getInstance();
$db = $service->client();

$collections = [
    'customers' => [['key' => ['email' => 1], 'unique' => true], ['key' => ['ssn' => 1], 'unique' => true]],
    'departments' => [['key' => ['name' => 1], 'unique' => true]],
    'bank_employees' => [['key' => ['email' => 1], 'unique' => true], ['key' => ['department_id' => 1]]],
    'loan_products' => [['key' => ['product_name' => 1]]],
    'loan_applications' => [['key' => ['application_reference' => 1], 'unique' => true]],
    'collaterals' => [['key' => ['customer_id' => 1]]],
    'loans' => [['key' => ['application_id' => 1]]],
    'documents' => [['key' => ['customer_id' => 1]]],
    'payment_schedules' => [['key' => ['loan_id' => 1], 'unique' => false]],
    'payment_transactions' => [['key' => ['loan_id' => 1]]],
    'notifications' => [['key' => ['recipient_id' => 1]]],
    'audit_logs' => [['key' => ['user_id' => 1]]],
    'refresh_tokens' => [['key' => ['token' => 1], 'unique' => true]]
];

foreach ($collections as $name => $indexes) {
    echo "Ensuring collection: $name\n";
    try {
        // createCollection is safe (library will ignore if already exists)
        $service->client()->createIndex($name, ['_id' => 1]); // ensure collection exists (cheap)
        foreach ($indexes as $idx) {
            $keys = $idx['key'];
            $options = $idx['unique'] ?? false ? ['unique' => true] : [];
            $service->client()->createIndex($name, $keys, $options);
        }
        echo " ✓ $name OK\n";
    } catch (Exception $e) {
        echo " ! $name: " . $e->getMessage() . "\n";
    }
}

// seed departments if absent
$departmentsCount = $service->client()->count('departments');
if ($departmentsCount === 0) {
    $service->client()->insertOne('departments', [
        'name' => 'Loan Department',
        'description' => 'Handles all loan-related operations',
        'created_at' => time(),
        'updated_at' => time()
    ]);
    echo "Seeded departments\n";
}

echo "Migrations finished.\n";

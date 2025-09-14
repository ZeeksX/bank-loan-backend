<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Services\DatabaseService;

try {
    $dbService = App\Services\DatabaseService::getInstance();
    $GLOBALS['mongo'] = $dbService->client();
    $GLOBALS['mongo_db_name'] = getenv('DB_DATABASE') ?: null;
} catch (Exception $e) {
    error_log('Database init failed: ' . $e->getMessage());
    if (getenv('APP_DEBUG') === 'true') {
        die('Database init failed: ' . $e->getMessage());
    }
    die('Database connection error.');
}

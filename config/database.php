<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Services\DatabaseService;

try {
    $dbService = App\Services\DatabaseService::getInstance();
    $GLOBALS['mysql'] = $dbService->client(); 
    $GLOBALS['mysql_db_name'] = getenv('MYSQL_DATABASE') ?: 'bank_loan_db';
} catch (Exception $e) {
    error_log('Database init failed: ' . $e->getMessage());
    if (getenv('APP_DEBUG') === 'true') {
        die('Database init failed: ' . $e->getMessage());
    }
    die('Database connection error.');
}
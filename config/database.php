<?php
// File: config/database.php

// Load our custom MongoDB client
require_once __DIR__ . '/../src/Database/MongoClient.php';

try {
    // Get MongoDB connection details
    $mongoUri = getenv('MONGODB_URI');
    $dbName = getenv('DB_DATABASE') ?: 'bank_loan_db';
    
    if (empty($mongoUri)) {
        throw new Exception('MONGODB_URI environment variable is not set');
    }
    
    // Try to connect using DatabaseService
    $dbService = App\Services\DatabaseService::getInstance();
    $GLOBALS['mongo_db'] = $dbService->getClient();
    $GLOBALS['mongo_client_type'] = $dbService->getClientType();
    
    if (getenv('APP_DEBUG') === 'true') {
        error_log("MongoDB connected successfully using " . $dbService->getClientType() . " client");
    }
    
} catch (Exception $e) {
    error_log("MongoDB connection failed: " . $e->getMessage());
    
    if (getenv('APP_DEBUG') === 'true') {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die("Database connection error. Please try again later.");
    }
}

// Helper functions
function getDatabase() {
    return $GLOBALS['mongo_db'] ?? null;
}

function getDatabaseClientType() {
    return $GLOBALS['mongo_client_type'] ?? 'unknown';
}
<?php
// File: config/database.php

require_once __DIR__ . '/../vendor/autoload.php';

// Load our custom MongoDB client if the library isn't available
if (!class_exists('MongoDB\Client')) {
    require_once __DIR__ . '/../src/Database/MongoClient.php';
}

try {
    // Get MongoDB URI from environment variable
    $mongoUri = getenv('MONGODB_URI');
    
    if (empty($mongoUri)) {
        throw new Exception('MONGODB_URI environment variable is not set');
    }
    
    // Extract database name from URI or use environment variable
    $dbName = getenv('DB_DATABASE');
    if (empty($dbName)) {
        // Try to extract from URI
        preg_match('/mongodb(\+srv)?:\/\/[^/]+\/([^?]+)/', $mongoUri, $matches);
        $dbName = $matches[2] ?? 'bank_loan_db';
    }
    
    // Try to use MongoDB library first, fall back to our custom client
    if (class_exists('MongoDB\Client')) {
        $mongo = new MongoDB\Client($mongoUri);
        $mongo->listDatabases(); // Test connection
        $db = $mongo->selectDatabase($dbName);
        $GLOBALS['mongo_db'] = $db;
        $GLOBALS['mongo_client_type'] = 'library';
    } else {
        // Use our custom client
        $mongo = new App\Database\MongoClient($mongoUri, $dbName);
        $mongo->ping(); // Test connection
        $GLOBALS['mongo_db'] = $mongo;
        $GLOBALS['mongo_client_type'] = 'custom';
    }
    
    // Only log in development mode
    if (getenv('APP_DEBUG') === 'true') {
        error_log("MongoDB connected successfully to database: " . $dbName . " using " . $GLOBALS['mongo_client_type'] . " client");
    }
    
} catch (Exception $e) {
    error_log("MongoDB connection failed: " . $e->getMessage());
    
    // In production, don't show detailed errors
    if (getenv('APP_DEBUG') === 'true') {
        die("MongoDB connection failed: " . $e->getMessage());
    } else {
        die("Database connection error. Please try again later.");
    }
}

// Helper function to get database instance
function getDatabase() {
    return $GLOBALS['mongo_db'] ?? null;
}

// Helper function to check client type
function getDatabaseClientType() {
    return $GLOBALS['mongo_client_type'] ?? 'unknown';
}
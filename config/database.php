<?php
// File: config/database.php
require_once __DIR__ . '/../vendor/autoload.php';

try {
    // Get MongoDB URI from environment variable
    $mongoUri = getenv('MONGODB_URI');
    
    if (empty($mongoUri)) {
        throw new Exception('MONGODB_URI environment variable is not set');
    }
    
    // MongoDB connection using the Atlas URI
    $mongo = new MongoDB\Client($mongoUri);
    
    // Test the connection by listing databases (this will trigger authentication)
    $mongo->listDatabases();
    
    // Extract database name from URI or use environment variable
    $dbName = getenv('DB_DATABASE');
    if (empty($dbName)) {
        // Try to extract from URI - for Atlas, it's usually in the path
        preg_match('/mongodb\+srv:\/\/[^/]+\/([^?]+)/', $mongoUri, $matches);
        $dbName = $matches[1] ?? 'bank_loan_db';
    }
    
    $db = $mongo->selectDatabase($dbName);
    
    // Store the database instance for later use
    $GLOBALS['mongo_db'] = $db;
    
    // Only log in development mode
    if (getenv('APP_DEBUG') === 'true') {
        error_log("MongoDB connected successfully to database: " . $dbName);
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
?>
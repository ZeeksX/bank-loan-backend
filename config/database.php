<?php
// File: config/database.php

require_once __DIR__ . '/../vendor/autoload.php';

// Load our custom MongoDB client as fallback
require_once __DIR__ . '/../src/Database/MongoClient.php';

try {
    // Get MongoDB connection details
    $mongoUri = getenv('MONGODB_URI');
    $dbName = getenv('DB_DATABASE') ?: 'bank_loan_db';
    
    if (empty($mongoUri)) {
        // Build URI from individual components if MONGODB_URI not available
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('MONGO_PORT') ?: '27017';
        $username = getenv('MONGO_INITDB_ROOT_USERNAME');
        $password = getenv('MONGO_INITDB_ROOT_PASSWORD');
        
        if ($username && $password) {
            $mongoUri = "mongodb://{$username}:{$password}@{$host}:{$port}/{$dbName}?authSource=admin";
        } else {
            $mongoUri = "mongodb://{$host}:{$port}/{$dbName}";
        }
    }
    
    // Extract database name from URI if not explicitly set
    if (empty($dbName) || $dbName === 'Cluster0') {
        preg_match('/mongodb(\+srv)?:\/\/[^\/]+\/([^?]+)/', $mongoUri, $matches);
        if (isset($matches[2]) && $matches[2] !== '') {
            $dbName = $matches[2];
        } else {
            $dbName = 'bank_loan_db'; // fallback
        }
    }
    
    $connectionSuccess = false;
    $clientType = 'none';
    $mongoClient = null;
    
    // Try MongoDB library first (preferred method)
    if (class_exists('MongoDB\Client')) {
        try {
            $mongoClient = new MongoDB\Client($mongoUri, [
                'connectTimeoutMS' => 5000,
                'serverSelectionTimeoutMS' => 5000,
            ]);
            
            // Test the connection
            $mongoClient->selectDatabase($dbName)->command(['ping' => 1]);
            $db = $mongoClient->selectDatabase($dbName);
            $connectionSuccess = true;
            $clientType = 'library';
            
            if (getenv('APP_DEBUG') === 'true') {
                error_log("MongoDB connected successfully using MongoDB library to database: " . $dbName);
            }
            
        } catch (Exception $e) {
            if (getenv('APP_DEBUG') === 'true') {
                error_log("MongoDB library connection failed: " . $e->getMessage());
            }
        }
    }
    
    // Fallback to custom MongoDB client using native PHP MongoDB driver
    if (!$connectionSuccess && extension_loaded('mongodb')) {
        try {
            $mongoClient = new App\Database\MongoClient($mongoUri, $dbName);
            $mongoClient->ping();
            $db = $mongoClient;
            $connectionSuccess = true;
            $clientType = 'custom';
            
            if (getenv('APP_DEBUG') === 'true') {
                error_log("MongoDB connected successfully using custom client to database: " . $dbName);
            }
            
        } catch (Exception $e) {
            if (getenv('APP_DEBUG') === 'true') {
                error_log("Custom MongoDB client connection failed: " . $e->getMessage());
            }
        }
    }
    
    // Final fallback - create a dummy client that stores data in files (for emergency situations)
    if (!$connectionSuccess) {
        require_once __DIR__ . '/../src/Database/FileStorageClient.php';
        try {
            $mongoClient = new App\Database\FileStorageClient($dbName);
            $db = $mongoClient;
            $connectionSuccess = true;
            $clientType = 'file';
            
            error_log("WARNING: Using file storage fallback. MongoDB connection failed. Data will be stored in files.");
            
        } catch (Exception $e) {
            error_log("All database connection methods failed: " . $e->getMessage());
        }
    }
    
    if (!$connectionSuccess) {
        throw new Exception("All database connection attempts failed");
    }
    
    // Store in globals for easy access
    $GLOBALS['mongo_db'] = $db;
    $GLOBALS['mongo_client'] = $mongoClient;
    $GLOBALS['mongo_client_type'] = $clientType;
    $GLOBALS['mongo_db_name'] = $dbName;
    
} catch (Exception $e) {
    error_log("Database initialization failed: " . $e->getMessage());
    
    // In production, don't show detailed errors
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

function getDatabaseClient() {
    return $GLOBALS['mongo_client'] ?? null;
}

function getDatabaseClientType() {
    return $GLOBALS['mongo_client_type'] ?? 'unknown';
}

function getDatabaseName() {
    return $GLOBALS['mongo_db_name'] ?? 'bank_loan_db';
}

// Helper function to create consistent timestamps
function createTimestamp() {
    return time(); // Unix timestamp - consistent across all systems
}

// Helper function to create MongoDB date object (when using MongoDB library)
function createMongoDate($timestamp = null) {
    $ts = $timestamp ?: time();
    
    if (class_exists('MongoDB\BSON\UTCDateTime')) {
        return new MongoDB\BSON\UTCDateTime($ts * 1000); // MongoDB expects milliseconds
    }
    
    return $ts; // Fallback to unix timestamp
}

// Helper function to convert MongoDB date to timestamp
function mongoDateToTimestamp($mongoDate) {
    if ($mongoDate instanceof MongoDB\BSON\UTCDateTime) {
        return $mongoDate->toDateTime()->getTimestamp();
    }
    
    if (is_object($mongoDate) && isset($mongoDate->sec)) {
        return $mongoDate->sec;
    }
    
    if (is_numeric($mongoDate)) {
        return (int)$mongoDate;
    }
    
    return strtotime($mongoDate) ?: time();
}
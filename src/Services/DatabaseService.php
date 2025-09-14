<?php
// File: src/Services/DatabaseService.php

namespace App\Services;

use Exception;

class DatabaseService {
    private static $instance = null;
    private $client;
    private $clientType;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            // Get MongoDB URI from environment
            $mongoUri = getenv('MONGODB_URI');
            $dbName = getenv('DB_DATABASE') ?: 'bank_loan_db';
            
            if (empty($mongoUri)) {
                throw new Exception('MONGODB_URI environment variable is not set');
            }
            
            // Try MongoDB library first
            if (class_exists('MongoDB\Client')) {
                $this->client = new \MongoDB\Client($mongoUri);
                $this->client->selectDatabase($dbName)->command(['ping' => 1]);
                $this->clientType = 'library';
            } 
            // Fallback to custom client
            else if (extension_loaded('mongodb')) {
                $this->client = new \App\Database\MongoClient($mongoUri, $dbName);
                $this->clientType = 'custom';
            } 
            // Final fallback to file storage
            else {
                require_once __DIR__ . '/../Database/FileStorageClient.php';
                $this->client = new \App\Database\FileStorageClient($dbName);
                $this->clientType = 'file';
                error_log("WARNING: Using file storage fallback. MongoDB extension not available.");
            }
            
        } catch (Exception $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getClient() {
        return $this->client;
    }
    
    public function getClientType() {
        return $this->clientType;
    }
    
    // Unified methods
    public function insertOne($collection, $document) {
        if ($this->clientType === 'library') {
            $result = $this->client->$collection->insertOne($document);
            return [
                'insertedId' => (string)$result->getInsertedId(),
                'insertedCount' => $result->getInsertedCount()
            ];
        } else {
            return $this->client->insertOne($collection, $document);
        }
    }
    
    public function findOne($collection, $filter = []) {
        if ($this->clientType === 'library') {
            $result = $this->client->$collection->findOne($filter);
            return $result ? (array)$result : null;
        } else {
            return $this->client->findOne($collection, $filter);
        }
    }
    
    public function find($collection, $filter = [], $options = []) {
        if ($this->clientType === 'library') {
            $cursor = $this->client->$collection->find($filter, $options);
            $results = [];
            foreach ($cursor as $document) {
                $results[] = (array)$document;
            }
            return $results;
        } else {
            return $this->client->find($collection, $filter, $options);
        }
    }
    
    public function updateOne($collection, $filter, $update) {
        if ($this->clientType === 'library') {
            $result = $this->client->$collection->updateOne($filter, $update);
            return [
                'matchedCount' => $result->getMatchedCount(),
                'modifiedCount' => $result->getModifiedCount()
            ];
        } else {
            return $this->client->updateOne($collection, $filter, $update);
        }
    }
    
    public function count($collection, $filter = []) {
        if ($this->clientType === 'library') {
            return $this->client->$collection->countDocuments($filter);
        } else {
            return $this->client->count($collection, $filter);
        }
    }
    
    public function ping() {
        if ($this->clientType === 'library') {
            try {
                $this->client->selectDatabase('admin')->command(['ping' => 1]);
                return true;
            } catch (Exception $e) {
                return false;
            }
        } else {
            return $this->client->ping();
        }
    }
}
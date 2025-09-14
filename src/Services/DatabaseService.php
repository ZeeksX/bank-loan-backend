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
            // Get the database connection from config/database.php
            $this->client = getDatabase();
            $this->clientType = getDatabaseClientType();
            
            if (!$this->client) {
                throw new Exception('Database connection not established');
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
    
    // Unified methods that work with both clients
    public function insertOne($collection, $document) {
        if ($this->clientType === 'library') {
            $result = $this->client->$collection->insertOne($document);
            return [
                'insertedId' => (string)$result->getInsertedId(),
                'insertedCount' => $result->getInsertedCount()
            ];
        } else {
            // Custom client
            $insertedCount = $this->client->insertOne($collection, $document);
            return [
                'insertedId' => '', // Custom client doesn't return ID directly
                'insertedCount' => $insertedCount
            ];
        }
    }
    
    public function findOne($collection, $filter = [], $options = []) {
        if ($this->clientType === 'library') {
            return $this->client->$collection->findOne($filter, $options);
        } else {
            // Custom client - merge options with limit
            $customOptions = array_merge($options, ['limit' => 1]);
            return $this->client->findOne($collection, $filter, $customOptions);
        }
    }
    
    public function find($collection, $filter = [], $options = []) {
        if ($this->clientType === 'library') {
            $cursor = $this->client->$collection->find($filter, $options);
            return $cursor->toArray();
        } else {
            return $this->client->find($collection, $filter, $options);
        }
    }
    
    public function updateOne($collection, $filter, $update, $options = []) {
        if ($this->clientType === 'library') {
            $result = $this->client->$collection->updateOne($filter, $update, $options);
            return [
                'matchedCount' => $result->getMatchedCount(),
                'modifiedCount' => $result->getModifiedCount()
            ];
        } else {
            // Custom client - simple update
            $modifiedCount = $this->client->updateOne($collection, $filter, $update);
            return [
                'matchedCount' => $modifiedCount, // Custom client doesn't distinguish
                'modifiedCount' => $modifiedCount
            ];
        }
    }
    
    public function deleteOne($collection, $filter, $options = []) {
        if ($this->clientType === 'library') {
            $result = $this->client->$collection->deleteOne($filter, $options);
            return [
                'deletedCount' => $result->getDeletedCount()
            ];
        } else {
            // Custom client
            $deletedCount = $this->client->deleteOne($collection, $filter);
            return [
                'deletedCount' => $deletedCount
            ];
        }
    }
    
    public function count($collection, $filter = [], $options = []) {
        if ($this->clientType === 'library') {
            return $this->client->$collection->countDocuments($filter, $options);
        } else {
            return $this->client->count($collection, $filter);
        }
    }
    
    // Helper method to create MongoDB-compatible date
    public function createDate($timestamp = null) {
        if ($this->clientType === 'library' && class_exists('MongoDB\BSON\UTCDateTime')) {
            return new \MongoDB\BSON\UTCDateTime($timestamp ? $timestamp * 1000 : time() * 1000);
        } else {
            // Fallback: use regular date string or timestamp
            return $timestamp ?: time();
        }
    }
    
    // Helper method to convert MongoDB date to PHP timestamp
    public function convertDate($mongoDate) {
        if ($this->clientType === 'library' && $mongoDate instanceof \MongoDB\BSON\UTCDateTime) {
            return $mongoDate->toDateTime()->getTimestamp();
        } else if (is_object($mongoDate) && isset($mongoDate->sec)) {
            // Handle legacy MongoDB date format
            return $mongoDate->sec;
        } else if (is_numeric($mongoDate)) {
            return $mongoDate;
        } else {
            return strtotime($mongoDate);
        }
    }
}
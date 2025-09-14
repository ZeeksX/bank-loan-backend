<?php
// File: src/Database/MongoClient.php

namespace App\Database;

use Exception;

class MongoClient {
    private $manager;
    private $database;
    
    public function __construct($uri, $database) {
        // Check if MongoDB extension is available
        if (!extension_loaded('mongodb')) {
            throw new Exception('MongoDB extension is not loaded');
        }
        
        try {
            $this->manager = new \MongoDB\Driver\Manager($uri);
            $this->database = $database;
            
            // Test connection
            $this->ping();
        } catch (Exception $e) {
            throw new Exception("MongoDB connection failed: " . $e->getMessage());
        }
    }
    
    public function ping() {
        try {
            $command = new \MongoDB\Driver\Command(['ping' => 1]);
            $this->manager->executeCommand($this->database, $command);
            return true;
        } catch (Exception $e) {
            throw new Exception("Ping failed: " . $e->getMessage());
        }
    }
    
    public function insertOne($collection, $document) {
        try {
            // Add timestamps
            $now = time();
            $document['created_at'] = $now;
            $document['updated_at'] = $now;
            
            $bulk = new \MongoDB\Driver\BulkWrite();
            $id = $bulk->insert($document);
            
            $result = $this->manager->executeBulkWrite($this->database . '.' . $collection, $bulk);
            
            return [
                'insertedId' => (string)$id,
                'insertedCount' => $result->getInsertedCount()
            ];
        } catch (Exception $e) {
            throw new Exception("Insert failed: " . $e->getMessage());
        }
    }
    
    public function findOne($collection, $filter = []) {
        try {
            $query = new \MongoDB\Driver\Query($filter, ['limit' => 1]);
            $cursor = $this->manager->executeQuery($this->database . '.' . $collection, $query);
            
            foreach ($cursor as $document) {
                return (array)$document;
            }
            return null;
        } catch (Exception $e) {
            throw new Exception("Find failed: " . $e->getMessage());
        }
    }
    
    public function find($collection, $filter = [], $options = []) {
        try {
            $query = new \MongoDB\Driver\Query($filter, $options);
            $cursor = $this->manager->executeQuery($this->database . '.' . $collection, $query);
            
            $results = [];
            foreach ($cursor as $document) {
                $results[] = (array)$document;
            }
            return $results;
        } catch (Exception $e) {
            throw new Exception("Find failed: " . $e->getMessage());
        }
    }
    
    public function updateOne($collection, $filter, $update) {
        try {
            // Add updated_at timestamp
            if (isset($update['$set'])) {
                $update['$set']['updated_at'] = time();
            } else {
                $update = ['$set' => array_merge($update, ['updated_at' => time()])];
            }
            
            $bulk = new \MongoDB\Driver\BulkWrite();
            $bulk->update($filter, $update);
            
            $result = $this->manager->executeBulkWrite($this->database . '.' . $collection, $bulk);
            
            return [
                'matchedCount' => $result->getMatchedCount(),
                'modifiedCount' => $result->getModifiedCount()
            ];
        } catch (Exception $e) {
            throw new Exception("Update failed: " . $e->getMessage());
        }
    }
    
    public function deleteOne($collection, $filter) {
        try {
            $bulk = new \MongoDB\Driver\BulkWrite();
            $bulk->delete($filter, ['limit' => 1]);
            
            $result = $this->manager->executeBulkWrite($this->database . '.' . $collection, $bulk);
            
            return [
                'deletedCount' => $result->getDeletedCount()
            ];
        } catch (Exception $e) {
            throw new Exception("Delete failed: " . $e->getMessage());
        }
    }
    
    public function count($collection, $filter = []) {
        try {
            $command = new \MongoDB\Driver\Command([
                'count' => $collection,
                'query' => $filter
            ]);
            
            $cursor = $this->manager->executeCommand($this->database, $command);
            $result = current($cursor->toArray());
            
            return $result->n ?? 0;
        } catch (Exception $e) {
            throw new Exception("Count failed: " . $e->getMessage());
        }
    }
}
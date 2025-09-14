<?php
// File: src/Database/MongoClient.php

namespace App\Database;

class MongoClient {
    private $manager;
    private $database;
    
    public function __construct($uri, $database) {
        $this->manager = new \MongoDB\Driver\Manager($uri);
        $this->database = $database;
    }
    
    public function ping() {
        $command = new \MongoDB\Driver\Command(['ping' => 1]);
        try {
            $result = $this->manager->executeCommand('admin', $command);
            return true;
        } catch (Exception $e) {
            throw new Exception("Connection failed: " . $e->getMessage());
        }
    }
    
    public function insertOne($collection, $document) {
        $bulk = new \MongoDB\Driver\BulkWrite;
        $bulk->insert($document);
        
        $result = $this->manager->executeBulkWrite($this->database . '.' . $collection, $bulk);
        return $result->getInsertedCount();
    }
    
    public function findOne($collection, $filter = []) {
        $query = new \MongoDB\Driver\Query($filter, ['limit' => 1]);
        $cursor = $this->manager->executeQuery($this->database . '.' . $collection, $query);
        
        foreach ($cursor as $document) {
            return $document;
        }
        return null;
    }
    
    public function find($collection, $filter = [], $options = []) {
        $query = new \MongoDB\Driver\Query($filter, $options);
        $cursor = $this->manager->executeQuery($this->database . '.' . $collection, $query);
        
        $results = [];
        foreach ($cursor as $document) {
            $results[] = $document;
        }
        return $results;
    }
    
    public function updateOne($collection, $filter, $update) {
        $bulk = new \MongoDB\Driver\BulkWrite;
        $bulk->update($filter, $update);
        
        $result = $this->manager->executeBulkWrite($this->database . '.' . $collection, $bulk);
        return $result->getModifiedCount();
    }
    
    public function deleteOne($collection, $filter) {
        $bulk = new \MongoDB\Driver\BulkWrite;
        $bulk->delete($filter, ['limit' => 1]);
        
        $result = $this->manager->executeBulkWrite($this->database . '.' . $collection, $bulk);
        return $result->getDeletedCount();
    }
    
    public function count($collection, $filter = []) {
        $command = new \MongoDB\Driver\Command([
            'count' => $collection,
            'query' => $filter
        ]);
        
        $cursor = $this->manager->executeCommand($this->database, $command);
        $result = $cursor->toArray();
        return $result[0]->n ?? 0;
    }
}
?>
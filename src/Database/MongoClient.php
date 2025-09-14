<?php
// File: src/Database/MongoClient.php

namespace App\Database;

use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Query;
use MongoDB\Driver\Command;
use MongoDB\BSON\ObjectId;
use Exception;

class MongoClient {
    private $manager;
    private $database;
    private $isConnected = false;
    
    public function __construct($uri, $database) {
        $this->database = $database;
        
        try {
            // Create manager with timeout options
            $this->manager = new Manager($uri, [
                'connectTimeoutMS' => 10000,
                'serverSelectionTimeoutMS' => 5000,
                'socketTimeoutMS' => 5000,
            ]);
            
            // Test the connection
            $this->ping();
            $this->isConnected = true;
            
        } catch (Exception $e) {
            throw new Exception("MongoDB connection failed: " . $e->getMessage());
        }
    }
    
    public function ping() {
        try {
            $command = new Command(['ping' => 1]);
            $cursor = $this->manager->executeCommand('admin', $command);
            $response = $cursor->toArray();
            
            if (empty($response) || !isset($response[0]->ok) || $response[0]->ok !== 1) {
                throw new Exception("Ping failed - invalid response");
            }
            
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Connection test failed: " . $e->getMessage());
        }
    }
    
    public function insertOne($collection, $document) {
        try {
            // Add timestamps if not present
            if (!isset($document['created_at'])) {
                $document['created_at'] = time();
            }
            if (!isset($document['updated_at'])) {
                $document['updated_at'] = time();
            }
            
            // Generate ObjectId if _id not present
            if (!isset($document['_id'])) {
                $document['_id'] = new ObjectId();
            }
            
            $bulk = new BulkWrite();
            $insertedId = $bulk->insert($document);
            
            $result = $this->manager->executeBulkWrite($this->database . '.' . $collection, $bulk);
            
            return [
                'insertedId' => (string)$insertedId,
                'insertedCount' => $result->getInsertedCount(),
                'acknowledged' => true
            ];
            
        } catch (Exception $e) {
            throw new Exception("Insert operation failed: " . $e->getMessage());
        }
    }
    
    public function insertMany($collection, $documents) {
        try {
            $bulk = new BulkWrite();
            $insertedIds = [];
            
            foreach ($documents as $document) {
                // Add timestamps if not present
                if (!isset($document['created_at'])) {
                    $document['created_at'] = time();
                }
                if (!isset($document['updated_at'])) {
                    $document['updated_at'] = time();
                }
                
                // Generate ObjectId if _id not present
                if (!isset($document['_id'])) {
                    $document['_id'] = new ObjectId();
                }
                
                $insertedIds[] = (string)$bulk->insert($document);
            }
            
            $result = $this->manager->executeBulkWrite($this->database . '.' . $collection, $bulk);
            
            return [
                'insertedIds' => $insertedIds,
                'insertedCount' => $result->getInsertedCount(),
                'acknowledged' => true
            ];
            
        } catch (Exception $e) {
            throw new Exception("Insert many operation failed: " . $e->getMessage());
        }
    }
    
    public function findOne($collection, $filter = [], $options = []) {
        try {
            // Convert string IDs to ObjectId
            $filter = $this->convertStringIdsToObjectId($filter);
            
            $queryOptions = array_merge(['limit' => 1], $options);
            $query = new Query($filter, $queryOptions);
            
            $cursor = $this->manager->executeQuery($this->database . '.' . $collection, $query);
            $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
            
            foreach ($cursor as $document) {
                // Convert ObjectId to string for _id field
                if (isset($document['_id']) && $document['_id'] instanceof ObjectId) {
                    $document['_id'] = (string)$document['_id'];
                }
                return $document;
            }
            
            return null;
            
        } catch (Exception $e) {
            throw new Exception("Find one operation failed: " . $e->getMessage());
        }
    }
    
    public function find($collection, $filter = [], $options = []) {
        try {
            // Convert string IDs to ObjectId
            $filter = $this->convertStringIdsToObjectId($filter);
            
            $query = new Query($filter, $options);
            $cursor = $this->manager->executeQuery($this->database . '.' . $collection, $query);
            $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
            
            $results = [];
            foreach ($cursor as $document) {
                // Convert ObjectId to string for _id field
                if (isset($document['_id']) && $document['_id'] instanceof ObjectId) {
                    $document['_id'] = (string)$document['_id'];
                }
                $results[] = $document;
            }
            
            return $results;
            
        } catch (Exception $e) {
            throw new Exception("Find operation failed: " . $e->getMessage());
        }
    }
    
    public function updateOne($collection, $filter, $update, $options = []) {
        try {
            // Convert string IDs to ObjectId
            $filter = $this->convertStringIdsToObjectId($filter);
            
            // Add updated_at timestamp if not in update
            if (isset($update['$set']) && !isset($update['$set']['updated_at'])) {
                $update['$set']['updated_at'] = time();
            } elseif (!isset($update['$set']) && !isset($update['updated_at'])) {
                $update['$set']['updated_at'] = time();
            }
            
            $bulk = new BulkWrite();
            $bulk->update($filter, $update, $options);
            
            $result = $this->manager->executeBulkWrite($this->database . '.' . $collection, $bulk);
            
            return [
                'matchedCount' => $result->getMatchedCount(),
                'modifiedCount' => $result->getModifiedCount(),
                'acknowledged' => true
            ];
            
        } catch (Exception $e) {
            throw new Exception("Update operation failed: " . $e->getMessage());
        }
    }
    
    public function updateMany($collection, $filter, $update, $options = []) {
        try {
            // Convert string IDs to ObjectId
            $filter = $this->convertStringIdsToObjectId($filter);
            
            // Add updated_at timestamp
            if (isset($update['$set']) && !isset($update['$set']['updated_at'])) {
                $update['$set']['updated_at'] = time();
            } elseif (!isset($update['$set'])) {
                $update['$set']['updated_at'] = time();
            }
            
            $options['multi'] = true; // Ensure multiple documents are updated
            
            $bulk = new BulkWrite();
            $bulk->update($filter, $update, $options);
            
            $result = $this->manager->executeBulkWrite($this->database . '.' . $collection, $bulk);
            
            return [
                'matchedCount' => $result->getMatchedCount(),
                'modifiedCount' => $result->getModifiedCount(),
                'acknowledged' => true
            ];
            
        } catch (Exception $e) {
            throw new Exception("Update many operation failed: " . $e->getMessage());
        }
    }
    
    public function deleteOne($collection, $filter, $options = []) {
        try {
            // Convert string IDs to ObjectId
            $filter = $this->convertStringIdsToObjectId($filter);
            
            $bulk = new BulkWrite();
            $bulk->delete($filter, ['limit' => 1] + $options);
            
            $result = $this->manager->executeBulkWrite($this->database . '.' . $collection, $bulk);
            
            return [
                'deletedCount' => $result->getDeletedCount(),
                'acknowledged' => true
            ];
            
        } catch (Exception $e) {
            throw new Exception("Delete operation failed: " . $e->getMessage());
        }
    }
    
    public function deleteMany($collection, $filter, $options = []) {
        try {
            // Convert string IDs to ObjectId
            $filter = $this->convertStringIdsToObjectId($filter);
            
            $bulk = new BulkWrite();
            $bulk->delete($filter, $options);
            
            $result = $this->manager->executeBulkWrite($this->database . '.' . $collection, $bulk);
            
            return [
                'deletedCount' => $result->getDeletedCount(),
                'acknowledged' => true
            ];
            
        } catch (Exception $e) {
            throw new Exception("Delete many operation failed: " . $e->getMessage());
        }
    }
    
    public function count($collection, $filter = []) {
        try {
            // Convert string IDs to ObjectId
            $filter = $this->convertStringIdsToObjectId($filter);
            
            $command = new Command([
                'count' => $collection,
                'query' => $filter
            ]);
            
            $cursor = $this->manager->executeCommand($this->database, $command);
            $result = $cursor->toArray();
            
            return isset($result[0]->n) ? (int)$result[0]->n : 0;
            
        } catch (Exception $e) {
            // Fallback: use aggregate to count
            try {
                return $this->countWithAggregate($collection, $filter);
            } catch (Exception $aggE) {
                throw new Exception("Count operation failed: " . $e->getMessage());
            }
        }
    }
    
    private function countWithAggregate($collection, $filter = []) {
        $pipeline = [];
        
        if (!empty($filter)) {
            $pipeline[] = ['$match' => $filter];
        }
        
        $pipeline[] = ['$count' => 'total'];
        
        $command = new Command([
            'aggregate' => $collection,
            'pipeline' => $pipeline,
            'cursor' => new \stdClass()
        ]);
        
        $cursor = $this->manager->executeCommand($this->database, $command);
        $result = $cursor->toArray();
        
        return isset($result[0]->total) ? (int)$result[0]->total : 0;
    }
    
    public function aggregate($collection, $pipeline, $options = []) {
        try {
            $command = new Command([
                'aggregate' => $collection,
                'pipeline' => $pipeline,
                'cursor' => new \stdClass()
            ] + $options);
            
            $cursor = $this->manager->executeCommand($this->database, $command);
            $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
            
            $results = [];
            foreach ($cursor as $document) {
                // Convert ObjectId to string for _id field
                if (isset($document['_id']) && $document['_id'] instanceof ObjectId) {
                    $document['_id'] = (string)$document['_id'];
                }
                $results[] = $document;
            }
            
            return $results;
            
        } catch (Exception $e) {
            throw new Exception("Aggregate operation failed: " . $e->getMessage());
        }
    }
    
    public function createIndex($collection, $keys, $options = []) {
        try {
            $command = new Command([
                'createIndexes' => $collection,
                'indexes' => [
                    [
                        'key' => $keys,
                        'name' => $this->generateIndexName($keys)
                    ] + $options
                ]
            ]);
            
            $result = $this->manager->executeCommand($this->database, $command);
            return $result->toArray()[0];
            
        } catch (Exception $e) {
            throw new Exception("Create index operation failed: " . $e->getMessage());
        }
    }
    
    public function listCollections() {
        try {
            $command = new Command(['listCollections' => 1]);
            $cursor = $this->manager->executeCommand($this->database, $command);
            
            $collections = [];
            foreach ($cursor as $collection) {
                $collections[] = $collection->name;
            }
            
            return $collections;
            
        } catch (Exception $e) {
            throw new Exception("List collections operation failed: " . $e->getMessage());
        }
    }
    
    // Helper method to convert string IDs to ObjectId
    private function convertStringIdsToObjectId($filter) {
        if (isset($filter['_id']) && is_string($filter['_id']) && strlen($filter['_id']) === 24) {
            try {
                $filter['_id'] = new ObjectId($filter['_id']);
            } catch (Exception $e) {
                // If conversion fails, leave as string
            }
        }
        
        // Handle nested filters
        foreach ($filter as $key => $value) {
            if (is_array($value)) {
                $filter[$key] = $this->convertStringIdsToObjectId($value);
            }
        }
        
        return $filter;
    }
    
    // Helper method to generate index name
    private function generateIndexName($keys) {
        $parts = [];
        foreach ($keys as $field => $direction) {
            $parts[] = $field . '_' . $direction;
        }
        return implode('_', $parts);
    }
    
    public function isConnected() {
        return $this->isConnected;
    }
    
    public function getDatabase() {
        return $this->database;
    }
}
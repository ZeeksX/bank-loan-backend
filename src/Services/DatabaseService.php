<?php
// File: src/Services/DatabaseService.php

namespace App\Services;

use Exception;

class DatabaseService
{
    private static $instance = null;
    private $client;
    private $clientType;

    private function __construct()
    {
        // Load database config which initializes the connection
        require_once __DIR__ . '/../../config/database.php';

        $this->client = getDatabase();
        $this->clientType = getDatabaseClientType();

        if (!$this->client) {
            throw new Exception('Database connection not established');
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getClientType()
    {
        return $this->clientType;
    }

    // Unified insert methods
    public function insertOne($collection, $document)
    {
        try {
            // Add timestamps
            if (!isset($document['created_at'])) {
                $document['created_at'] = createTimestamp();
            }
            if (!isset($document['updated_at'])) {
                $document['updated_at'] = createTimestamp();
            }

            if ($this->clientType === 'library') {
                $result = $this->client->$collection->insertOne($document);
                return [
                    'insertedId' => (string) $result->getInsertedId(),
                    'insertedCount' => $result->getInsertedCount(),
                    'acknowledged' => $result->isAcknowledged()
                ];
            } else {
                // Works with both custom and file storage clients
                return $this->client->insertOne($collection, $document);
            }
        } catch (Exception $e) {
            throw new Exception("Insert operation failed: " . $e->getMessage());
        }
    }

    public function insertMany($collection, $documents)
    {
        try {
            $currentTime = createTimestamp();

            // Add timestamps to all documents
            foreach ($documents as &$document) {
                if (!isset($document['created_at'])) {
                    $document['created_at'] = $currentTime;
                }
                if (!isset($document['updated_at'])) {
                    $document['updated_at'] = $currentTime;
                }
            }

            if ($this->clientType === 'library') {
                $result = $this->client->$collection->insertMany($documents);
                return [
                    'insertedIds' => array_map('strval', $result->getInsertedIds()),
                    'insertedCount' => $result->getInsertedCount(),
                    'acknowledged' => $result->isAcknowledged()
                ];
            } else {
                return $this->client->insertMany($collection, $documents);
            }
        } catch (Exception $e) {
            throw new Exception("Insert many operation failed: " . $e->getMessage());
        }
    }

    public function findOne($collection, $filter = [], $options = [])
    {
        try {
            if ($this->clientType === 'library') {
                $document = $this->client->$collection->findOne($filter, $options);
                return $document ? $document->toArray() : null;
            } else {
                return $this->client->findOne($collection, $filter, $options);
            }
        } catch (Exception $e) {
            throw new Exception("Find one operation failed: " . $e->getMessage());
        }
    }

    public function find($collection, $filter = [], $options = [])
    {
        try {
            if ($this->clientType === 'library') {
                $cursor = $this->client->$collection->find($filter, $options);
                $results = [];
                foreach ($cursor as $document) {
                    $results[] = $document->toArray();
                }
                return $results;
            } else {
                return $this->client->find($collection, $filter, $options);
            }
        } catch (Exception $e) {
            throw new Exception("Find operation failed: " . $e->getMessage());
        }
    }

    public function updateOne($collection, $filter, $update, $options = [])
    {
        try {
            // Add updated_at timestamp
            if (isset($update['$set'])) {
                $update['$set']['updated_at'] = createTimestamp();
            } else {
                $update['$set'] = ['updated_at' => createTimestamp()];
            }

            if ($this->clientType === 'library') {
                $result = $this->client->$collection->updateOne($filter, $update, $options);
                return [
                    'matchedCount' => $result->getMatchedCount(),
                    'modifiedCount' => $result->getModifiedCount(),
                    'acknowledged' => $result->isAcknowledged()
                ];
            } else {
                return $this->client->updateOne($collection, $filter, $update, $options);
            }
        } catch (Exception $e) {
            throw new Exception("Update operation failed: " . $e->getMessage());
        }
    }

    public function updateMany($collection, $filter, $update, $options = [])
    {
        try {
            // Add updated_at timestamp
            if (isset($update['$set'])) {
                $update['$set']['updated_at'] = createTimestamp();
            } else {
                $update['$set'] = ['updated_at' => createTimestamp()];
            }

            if ($this->clientType === 'library') {
                $result = $this->client->$collection->updateMany($filter, $update, $options);
                return [
                    'matchedCount' => $result->getMatchedCount(),
                    'modifiedCount' => $result->getModifiedCount(),
                    'acknowledged' => $result->isAcknowledged()
                ];
            } else {
                return $this->client->updateMany($collection, $filter, $update, $options);
            }
        } catch (Exception $e) {
            throw new Exception("Update many operation failed: " . $e->getMessage());
        }
    }

    public function deleteOne($collection, $filter, $options = [])
    {
        try {
            if ($this->clientType === 'library') {
                $result = $this->client->$collection->deleteOne($filter, $options);
                return [
                    'deletedCount' => $result->getDeletedCount(),
                    'acknowledged' => $result->isAcknowledged()
                ];
            } else {
                return $this->client->deleteOne($collection, $filter, $options);
            }
        } catch (Exception $e) {
            throw new Exception("Delete operation failed: " . $e->getMessage());
        }
    }

    public function deleteMany($collection, $filter, $options = [])
    {
        try {
            if ($this->clientType === 'library') {
                $result = $this->client->$collection->deleteMany($filter, $options);
                return [
                    'deletedCount' => $result->getDeletedCount(),
                    'acknowledged' => $result->isAcknowledged()
                ];
            } else {
                return $this->client->deleteMany($collection, $filter, $options);
            }
        } catch (Exception $e) {
            throw new Exception("Delete many operation failed: " . $e->getMessage());
        }
    }

    public function count($collection, $filter = [], $options = [])
    {
        try {
            if ($this->clientType === 'library') {
                return $this->client->$collection->countDocuments($filter, $options);
            } else {
                return $this->client->count($collection, $filter);
            }
        } catch (Exception $e) {
            throw new Exception("Count operation failed: " . $e->getMessage());
        }
    }

    public function aggregate($collection, $pipeline, $options = [])
    {
        try {
            if ($this->clientType === 'library') {
                $cursor = $this->client->$collection->aggregate($pipeline, $options);
                $results = [];
                foreach ($cursor as $document) {
                    $results[] = $document->toArray();
                }
                return $results;
            } else {
                return $this->client->aggregate($collection, $pipeline, $options);
            }
        } catch (Exception $e) {
            throw new Exception("Aggregate operation failed: " . $e->getMessage());
        }
    }

    // Helper methods for ID handling
    public function createObjectId($id = null)
    {
        if ($this->clientType === 'library' && class_exists('MongoDB\BSON\ObjectId')) {
            return $id ? new \MongoDB\BSON\ObjectId($id) : new \MongoDB\BSON\ObjectId();
        }

        // Fallback: return string ID
        return $id ?: uniqid('', true);
    }

    public function objectIdToString($objectId)
    {
        if (is_object($objectId) && method_exists($objectId, '__toString')) {
            return (string) $objectId;
        }

        return (string) $objectId;
    }

    public function isValidObjectId($id)
    {
        if ($this->clientType === 'library' && class_exists('MongoDB\BSON\ObjectId')) {
            try {
                new \MongoDB\BSON\ObjectId($id);
                return true;
            } catch (Exception $e) {
                return false;
            }
        }

        // For other clients, check if it's a reasonable ID format
        return is_string($id) && strlen($id) > 0;
    }

    // Transaction support (only for library client)
    public function startTransaction()
    {
        if ($this->clientType === 'library') {
            $session = $this->client->getMongo()->startSession();
            $session->startTransaction();
            return $session;
        }

        // Return null for non-transactional clients
        return null;
    }

    public function commitTransaction($session)
    {
        if ($session && method_exists($session, 'commitTransaction')) {
            $session->commitTransaction();
            $session->endSession();
        }
    }

    public function abortTransaction($session)
    {
        if ($session && method_exists($session, 'abortTransaction')) {
            $session->abortTransaction();
            $session->endSession();
        }
    }

    // Index management
    public function createIndex($collection, $keys, $options = [])
    {
        try {
            if ($this->clientType === 'library') {
                return $this->client->$collection->createIndex($keys, $options);
            } else if (method_exists($this->client, 'createIndex')) {
                return $this->client->createIndex($collection, $keys, $options);
            }

            // Index creation not supported for file storage
            return true;
        } catch (Exception $e) {
            throw new Exception("Create index operation failed: " . $e->getMessage());
        }
    }

    public function dropIndex($collection, $indexName)
    {
        try {
            if ($this->clientType === 'library') {
                return $this->client->$collection->dropIndex($indexName);
            }

            // Index dropping not supported for other clients
            return true;
        } catch (Exception $e) {
            throw new Exception("Drop index operation failed: " . $e->getMessage());
        }
    }

    public function listIndexes($collection)
    {
        try {
            if ($this->clientType === 'library') {
                $cursor = $this->client->$collection->listIndexes();
                $indexes = [];
                foreach ($cursor as $index) {
                    $indexes[] = $index->toArray();
                }
                return $indexes;
            }

            // Not supported for other clients
            return [];
        } catch (Exception $e) {
            return [];
        }
    }

    // Collection management
    public function listCollections()
    {
        try {
            if ($this->clientType === 'library') {
                $cursor = $this->client->listCollections();
                $collections = [];
                foreach ($cursor as $collection) {
                    $collections[] = $collection->getName();
                }
                return $collections;
            } else if (method_exists($this->client, 'listCollections')) {
                return $this->client->listCollections();
            }

            return [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function createCollection($collectionName, $options = [])
    {
        try {
            if ($this->clientType === 'library') {
                return $this->client->createCollection($collectionName, $options);
            }

            // Other clients create collections automatically
            return true;
        } catch (Exception $e) {
            throw new Exception("Create collection operation failed: " . $e->getMessage());
        }
    }

    public function dropCollection($collectionName)
    {
        try {
            if ($this->clientType === 'library') {
                return $this->client->$collectionName->drop();
            }

            // Collection dropping not implemented for other clients
            return true;
        } catch (Exception $e) {
            throw new Exception("Drop collection operation failed: " . $e->getMessage());
        }
    }

    // Health check
    public function ping()
    {
        try {
            if (method_exists($this->client, 'ping')) {
                return $this->client->ping();
            } else if ($this->clientType === 'library') {
                // Try a simple command
                $this->client->selectDatabase('admin')->command(['ping' => 1]);
                return true;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getStats()
    {
        if ($this->clientType === 'library') {
            try {
                $collections = $this->client->listCollections();
                $collectionNames = [];
                foreach ($collections as $collection) {
                    $collectionNames[] = $collection->getName();
                }

                return [
                    'database_name' => $this->client->getDatabaseName(),
                    'collections' => $collectionNames,
                    'client_type' => $this->clientType
                ];
            } catch (Exception $e) {
                return [
                    'database_name' => 'Unknown',
                    'collections' => [],
                    'client_type' => $this->clientType,
                    'error' => $e->getMessage()
                ];
            }
        } else {
            // For custom clients, return basic info
            return [
                'database_name' => 'Custom Client',
                'collections' => ['Unknown - Custom client'],
                'client_type' => $this->clientType
            ];
        }
    }

}
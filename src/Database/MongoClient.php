<?php
namespace App\Database;

use MongoDB\Client as MongoLibraryClient;
use MongoDB\BSON\ObjectId;
use Exception;

class MongoClient
{
    private MongoLibraryClient $client;
    private string $database;

    public function __construct(string $uri, string $database)
    {
        if (!class_exists(MongoLibraryClient::class)) {
            throw new Exception('mongodb/mongodb library is not installed (composer).');
        }

        $this->client = new MongoLibraryClient($uri, [
            'connectTimeoutMS' => 5000,
            'serverSelectionTimeoutMS' => 5000,
        ]);

        $this->database = $database;
        // test connection (throws on failure)
        $this->ping();
    }

    public function ping(): bool
    {
        try {
            $this->client->selectDatabase($this->database)->command(['ping' => 1]);
            return true;
        } catch (Exception $e) {
            throw new Exception('MongoDB ping failed: ' . $e->getMessage());
        }
    }

    public function insertOne(string $collection, array $doc): array
    {
        $doc['created_at'] ??= time();
        $doc['updated_at'] ??= time();
        $result = $this->client->selectCollection($this->database, $collection)->insertOne($doc);
        return [
            'insertedId' => (string)$result->getInsertedId(),
            'insertedCount' => $result->getInsertedCount(),
            'acknowledged' => $result->isAcknowledged()
        ];
    }

    public function insertMany(string $collection, array $docs): array
    {
        $now = time();
        foreach ($docs as &$d) {
            $d['created_at'] ??= $now;
            $d['updated_at'] ??= $now;
        }
        $result = $this->client->selectCollection($this->database, $collection)->insertMany($docs);
        return [
            'insertedIds' => array_map('strval', $result->getInsertedIds()),
            'insertedCount' => $result->getInsertedCount(),
            'acknowledged' => $result->isAcknowledged()
        ];
    }

    public function findOne(string $collection, array $filter = [], array $options = [])
    {
        $doc = $this->client->selectCollection($this->database, $collection)->findOne($filter, $options);
        return $doc ? $doc->getArrayCopy() : null;
    }

    public function find(string $collection, array $filter = [], array $options = []): array
    {
        $cursor = $this->client->selectCollection($this->database, $collection)->find($filter, $options);
        $results = [];
        foreach ($cursor as $doc) {
            $results[] = $doc->getArrayCopy();
        }
        return $results;
    }

    public function updateOne(string $collection, array $filter, array $update, array $options = []): array
    {
        $update['$set']['updated_at'] = time();
        $result = $this->client->selectCollection($this->database, $collection)->updateOne($filter, $update, $options);
        return [
            'matchedCount' => $result->getMatchedCount(),
            'modifiedCount' => $result->getModifiedCount(),
            'acknowledged' => $result->isAcknowledged()
        ];
    }

    public function deleteOne(string $collection, array $filter): array
    {
        $result = $this->client->selectCollection($this->database, $collection)->deleteOne($filter);
        return ['deletedCount' => $result->getDeletedCount(), 'acknowledged' => $result->isAcknowledged()];
    }

    public function createIndex(string $collection, array $keys, array $options = [])
    {
        return $this->client->selectCollection($this->database, $collection)->createIndex($keys, $options);
    }

    public function listCollections(): array
    {
        $cursor = $this->client->selectDatabase($this->database)->listCollections();
        $names = [];
        foreach ($cursor as $c) $names[] = $c->getName();
        return $names;
    }

    public function count(string $collection, array $filter = []): int
    {
        return (int)$this->client->selectCollection($this->database, $collection)->countDocuments($filter);
    }


    public function deleteMany(string $collection, array $filter): array
    {
        $result = $this->client->selectCollection($this->database, $collection)->deleteMany($filter);
        return ['deletedCount' => $result->getDeletedCount(), 'acknowledged' => $result->isAcknowledged()];
    }
}

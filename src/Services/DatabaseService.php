<?php
namespace App\Services;

use App\Database\MongoClient;
use Exception;

class DatabaseService
{
    private static ?self $instance = null;
    private MongoClient $dbClient;

    private function __construct()
    {
        $uri = getenv('MONGODB_URI') ?: $this->buildUriFromEnv();
        $db = getenv('DB_DATABASE') ?: $this->extractDbFromUri($uri) ?: 'bank_loan_db';
        $this->dbClient = new MongoClient($uri, $db);
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function client(): MongoClient
    {
        return $this->dbClient;
    }

    private function buildUriFromEnv(): string
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('MONGO_PORT') ?: '27017';
        $user = getenv('MONGO_INITDB_ROOT_USERNAME');
        $pass = getenv('MONGO_INITDB_ROOT_PASSWORD');
        $db = getenv('DB_DATABASE') ?: 'bank_loan_db';
        if ($user && $pass) {
            return "mongodb://{$user}:{$pass}@{$host}:{$port}/{$db}?authSource=admin";
        }
        return "mongodb://{$host}:{$port}/{$db}";
    }

    private function extractDbFromUri(string $uri): ?string
    {
        // Try to get the database name after the first '/' and before '?'
        if (preg_match('#mongodb(?:\+srv)?://[^/]+/([^?]+)#', $uri, $m)) {
            return $m[1];
        }
        return null;
    }

    public function ping(): bool
    {
        return $this->dbClient->ping();
    }


    public function insertOne(string $collection, array $doc): array
    {
        return $this->dbClient->insertOne($collection, $doc);
    }

    public function insertMany(string $collection, array $docs): array
    {
        return $this->dbClient->insertMany($collection, $docs);
    }

    public function findOne(string $collection, array $filter, array $options = []): ?array
    {
        return $this->dbClient->findOne($collection, $filter, $options);
    }

    public function count(string $collection, array $filter = []): int
    {
        return $this->dbClient->count($collection, $filter);
    }

    public function deleteMany(string $collection, array $filter): array
    {
        return $this->dbClient->deleteMany($collection, $filter);
    }
}

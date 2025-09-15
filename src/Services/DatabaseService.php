<?php
namespace App\Services;

use App\Database\MySQLClient;
use Exception;

class DatabaseService
{
    private static ?self $instance = null;
    private MySQLClient $dbClient;

    private function __construct()
    {
        $host = getenv('MYSQL_HOST') ?: 'mysql';
        $database = getenv('MYSQL_DATABASE') ?: 'bank_loan_db';
        $user = getenv('MYSQL_USER') ?: 'devuser';
        $password = getenv('MYSQL_PASSWORD') ?: 'devpass';
        $this->dbClient = new MySQLClient($host, $database, $user, $password);
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function client(): MySQLClient
    {
        return $this->dbClient;
    }

    public function ping(): bool
    {
        return $this->dbClient->ping();
    }

    public function insertOne(string $table, array $data): array
    {
        return $this->dbClient->insertOne($table, $data);
    }

    public function insertMany(string $table, array $rows): array
    {
        return $this->dbClient->insertMany($table, $rows);
    }

    public function findOne(string $table, array $filter, array $options = []): ?array
    {
        return $this->dbClient->findOne($table, $filter, $options);
    }

    public function count(string $table, array $filter = []): int
    {
        return $this->dbClient->count($table, $filter);
    }

    public function deleteMany(string $table, array $filter): array
    {
        return $this->dbClient->deleteMany($table, $filter);
    }
}
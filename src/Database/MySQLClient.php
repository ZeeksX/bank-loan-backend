<?php
namespace App\Database;

use PDO;
use PDOException;
use Exception;
use PDOStatement;

class MySQLClient
{
    private PDO $client;
    private string $database;

    public function __construct(string $host, string $database, string $user, string $password)
    {
        try {
            $this->client = new PDO(
                "mysql:host={$host};dbname={$database};charset=utf8mb4",
                $user,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            $this->database = $database;
            // Test connection
            $this->ping();
        } catch (PDOException $e) {
            throw new Exception('MySQL connection failed: ' . $e->getMessage());
        }
    }

    public function ping(): bool
    {
        try {
            $this->client->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            throw new Exception('MySQL ping failed: ' . $e->getMessage());
        }
    }

    public function insertOne(string $table, array $data): array
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        try {
            $stmt = $this->client->prepare($sql);
            $stmt->execute(array_values($data));
            $lastInsertId = $this->client->lastInsertId();
            return [
                'insertedId' => $lastInsertId,
                'insertedCount' => $stmt->rowCount(),
                'acknowledged' => true
            ];
        } catch (PDOException $e) {
            throw new Exception("Insert failed: " . $e->getMessage());
        }
    }

    public function insertMany(string $table, array $rows): array
    {
        if (empty($rows)) {
            return ['insertedIds' => [], 'insertedCount' => 0, 'acknowledged' => true];
        }

        $now = date('Y-m-d H:i:s');
        foreach ($rows as &$row) {
            $row['created_at'] = $row['created_at'] ?? $now;
            $row['updated_at'] = $row['updated_at'] ?? $now;
        }

        $columns = implode(', ', array_keys($rows[0]));
        $placeholders = implode(', ', array_fill(0, count($rows[0]), '?'));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        try {
            $this->client->beginTransaction();
            $insertedIds = [];
            $insertedCount = 0;

            foreach ($rows as $row) {
                $stmt = $this->client->prepare($sql);
                $stmt->execute(array_values($row));
                $insertedIds[] = $this->client->lastInsertId();
                $insertedCount += $stmt->rowCount();
            }

            $this->client->commit();
            return [
                'insertedIds' => $insertedIds,
                'insertedCount' => $insertedCount,
                'acknowledged' => true
            ];
        } catch (PDOException $e) {
            $this->client->rollBack();
            throw new Exception("Insert many failed: " . $e->getMessage());
        }
    }

    public function findOne(string $table, array $filter = [], array $options = []): ?array
    {
        $conditions = [];
        $params = [];
        foreach ($filter as $key => $value) {
            $conditions[] = "$key = ?";
            $params[] = $value;
        }
        $where = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        $sql = "SELECT * FROM {$table} {$where} LIMIT 1";

        try {
            $stmt = $this->client->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            throw new Exception("Find one failed: " . $e->getMessage());
        }
    }

    public function find(string $table, array $filter = [], array $options = []): array
    {
        $conditions = [];
        $params = [];
        foreach ($filter as $key => $value) {
            $conditions[] = "$key = ?";
            $params[] = $value;
        }
        $where = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        $sql = "SELECT * FROM {$table} {$where}";

        try {
            $stmt = $this->client->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Find failed: " . $e->getMessage());
        }
    }

    public function updateOne(string $table, array $filter, array $update, array $options = []): array
    {
        $update['updated_at'] = date('Y-m-d H:i:s');
        $set = [];
        $params = [];
        foreach ($update as $key => $value) {
            $set[] = "$key = ?";
            $params[] = $value;
        }
        $setClause = implode(', ', $set);

        $conditions = [];
        foreach ($filter as $key => $value) {
            $conditions[] = "$key = ?";
            $params[] = $value;
        }
        $where = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        $sql = "UPDATE {$table} SET {$setClause} {$where} LIMIT 1";

        try {
            $stmt = $this->client->prepare($sql);
            $stmt->execute($params);
            return [
                'matchedCount' => $stmt->rowCount(),
                'modifiedCount' => $stmt->rowCount(),
                'acknowledged' => true
            ];
        } catch (PDOException $e) {
            throw new Exception("Update failed: " . $e->getMessage());
        }
    }

    public function deleteOne(string $table, array $filter): array
    {
        $conditions = [];
        $params = [];
        foreach ($filter as $key => $value) {
            $conditions[] = "$key = ?";
            $params[] = $value;
        }
        $where = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        $sql = "DELETE FROM {$table} {$where} LIMIT 1";

        try {
            $stmt = $this->client->prepare($sql);
            $stmt->execute($params);
            return [
                'deletedCount' => $stmt->rowCount(),
                'acknowledged' => true
            ];
        } catch (PDOException $e) {
            throw new Exception("Delete failed: " . $e->getMessage());
        }
    }

    public function createIndex(string $table, array $keys, array $options = []): void
    {
        $indexParts = [];
        foreach ($keys as $key => $order) {
            $indexParts[] = "$key " . ($order === 1 ? 'ASC' : 'DESC');
        }
        $indexDefinition = implode(', ', $indexParts);
        $indexName = 'idx_' . implode('_', array_keys($keys));
        $unique = !empty($options['unique']) ? 'UNIQUE' : '';

        $sql = "CREATE $unique INDEX {$indexName} ON {$table} ({$indexDefinition})";

        try {
            $this->client->exec($sql);
        } catch (PDOException $e) {
            throw new Exception("Create index failed: " . $e->getMessage());
        }
    }

    public function listTables(): array
    {
        try {
            $stmt = $this->client->query("SHOW TABLES FROM {$this->database}");
            return array_column($stmt->fetchAll(), "Tables_in_{$this->database}");
        } catch (PDOException $e) {
            throw new Exception("List tables failed: " . $e->getMessage());
        }
    }

    public function count(string $table, array $filter = []): int
    {
        $conditions = [];
        $params = [];
        foreach ($filter as $key => $value) {
            $conditions[] = "$key = ?";
            $params[] = $value;
        }
        $where = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        $sql = "SELECT COUNT(*) FROM {$table} {$where}";

        try {
            $stmt = $this->client->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new Exception("Count failed: " . $e->getMessage());
        }
    }

    public function deleteMany(string $table, array $filter): array
    {
        $conditions = [];
        $params = [];
        foreach ($filter as $key => $value) {
            $conditions[] = "$key = ?";
            $params[] = $value;
        }
        $where = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        $sql = "DELETE FROM {$table} {$where}";

        try {
            $stmt = $this->client->prepare($sql);
            $stmt->execute($params);
            return [
                'deletedCount' => $stmt->rowCount(),
                'acknowledged' => true
            ];
        } catch (PDOException $e) {
            throw new Exception("Delete many failed: " . $e->getMessage());
        }
    }

    public function exec(string $sql): int
    {
        try {
            return $this->client->exec($sql);
        } catch (PDOException $e) {
            throw new Exception("SQL execution failed: " . $e->getMessage());
        }
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        try {
            $stmt = $this->client->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("SQL query failed: " . $e->getMessage());
        }
    }
}
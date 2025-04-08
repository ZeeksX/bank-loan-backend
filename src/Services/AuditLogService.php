<?php
// File: src/Services/AuditLogService.php

class AuditLogService
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    public function getAllLogs()
    {
        $stmt = $this->pdo->query("SELECT * FROM audit_logs");
        return $stmt->fetchAll();
    }

    public function getLogById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM audit_logs WHERE log_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function createLog(array $data)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO audit_logs (user_id, user_type, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
             VALUES (:user_id, :user_type, :action, :entity_type, :entity_id, :old_values, :new_values, :ip_address, :user_agent)"
        );
        $stmt->execute([
            'user_id' => $data['user_id'] ?? null,
            'user_type' => $data['user_type'],
            'action' => $data['action'],
            'entity_type' => $data['entity_type'],
            'entity_id' => $data['entity_id'],
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null
        ]);
        return $this->pdo->lastInsertId();
    }
}

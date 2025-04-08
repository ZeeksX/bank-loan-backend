<?php
// File: src/Controllers/AuditLogController.php

class AuditLogController
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    // GET /api/audit_logs
    public function index()
    {
        $stmt = $this->pdo->query("SELECT * FROM audit_logs");
        echo json_encode($stmt->fetchAll());
    }

    // GET /api/audit_logs/{id}
    public function show($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM audit_logs WHERE log_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode($stmt->fetch());
    }

    // POST /api/audit_logs
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("INSERT INTO audit_logs (user_id, user_type, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
            VALUES (:user_id, :user_type, :action, :entity_type, :entity_id, :old_values, :new_values, :ip_address, :user_agent)");
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
        echo json_encode(['message' => 'Audit log recorded']);
    }
}

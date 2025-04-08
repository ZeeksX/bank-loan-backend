<?php
// File: src/Controllers/NotificationController.php

class NotificationController
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    // GET /api/notifications
    public function index()
    {
        $stmt = $this->pdo->query("SELECT * FROM notifications");
        echo json_encode($stmt->fetchAll());
    }

    // GET /api/notifications/{id}
    public function show($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM notifications WHERE notification_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode($stmt->fetch());
    }

    // POST /api/notifications
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("INSERT INTO notifications (recipient_type, recipient_id, notification_type, subject, message, related_to, related_id, status)
            VALUES (:recipient_type, :recipient_id, :notification_type, :subject, :message, :related_to, :related_id, :status)");
        $stmt->execute([
            'recipient_type' => $data['recipient_type'],
            'recipient_id' => $data['recipient_id'],
            'notification_type' => $data['notification_type'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'related_to' => $data['related_to'],
            'related_id' => $data['related_id'] ?? null,
            'status' => $data['status'] ?? 'pending'
        ]);
        echo json_encode(['message' => 'Notification sent']);
    }
}

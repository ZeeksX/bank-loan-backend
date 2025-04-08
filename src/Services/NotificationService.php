<?php
// File: src/Services/NotificationService.php

class NotificationService
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    public function getAllNotifications()
    {
        $stmt = $this->pdo->query("SELECT * FROM notifications");
        return $stmt->fetchAll();
    }

    public function getNotificationById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM notifications WHERE notification_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function createNotification(array $data)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO notifications (recipient_type, recipient_id, notification_type, subject, message, related_to, related_id, status)
             VALUES (:recipient_type, :recipient_id, :notification_type, :subject, :message, :related_to, :related_id, :status)"
        );
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
        return $this->pdo->lastInsertId();
    }
}

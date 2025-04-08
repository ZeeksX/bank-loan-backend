<?php
namespace App\Models;

use PDO;

class Notification {
    protected $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function send(array $data) {
        $sql = "INSERT INTO notifications (recipient_type, recipient_id, notification_type, subject, message, related_to, related_id) 
                VALUES (:recipient_type, :recipient_id, :notification_type, :subject, :message, :related_to, :related_id)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
    }
}

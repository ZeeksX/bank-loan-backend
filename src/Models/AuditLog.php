<?php
namespace App\Models;

use PDO;

class AuditLog
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function log(array $data)
    {
        $sql = "INSERT INTO audit_logs (user_id, user_type, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent) 
                VALUES (:user_id, :user_type, :action, :entity_type, :entity_id, :old_values, :new_values, :ip_address, :user_agent)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
    }
}

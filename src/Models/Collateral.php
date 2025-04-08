<?php
namespace App\Models;

use PDO;

class Collateral {
    protected $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create(array $data) {
        $sql = "INSERT INTO collaterals (customer_id, collateral_type, description, estimated_value) 
                VALUES (:customer_id, :collateral_type, :description, :estimated_value)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }
}

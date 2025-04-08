<?php
namespace App\Models;

use PDO;

class BankEmployee {
    protected $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function all() {
        return $this->pdo->query("SELECT * FROM bank_employees")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data) {
        $sql = "INSERT INTO bank_employees (first_name, last_name, email, phone, department, position) 
                VALUES (:first_name, :last_name, :email, :phone, :department, :position)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }
}

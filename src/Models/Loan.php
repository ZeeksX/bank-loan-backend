<?php
namespace App\Models;

use PDO;

class Loan {
    protected $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create(array $data) {
        $sql = "INSERT INTO loans (application_id, customer_id, product_id, principal_amount, interest_rate, term, start_date, end_date, approved_by, approval_date) 
                VALUES (:application_id, :customer_id, :product_id, :principal_amount, :interest_rate, :term, :start_date, :end_date, :approved_by, :approval_date)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }
}

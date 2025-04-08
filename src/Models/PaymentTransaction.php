<?php
namespace App\Models;

use PDO;

class PaymentTransaction {
    protected $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create(array $data) {
        $sql = "INSERT INTO payment_transactions (loan_id, schedule_id, amount_paid, principal_portion, interest_portion, payment_date, payment_method, transaction_reference, processed_by) 
                VALUES (:loan_id, :schedule_id, :amount_paid, :principal_portion, :interest_portion, :payment_date, :payment_method, :transaction_reference, :processed_by)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }
}

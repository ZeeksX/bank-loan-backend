<?php
namespace App\Models;

use PDO;

class PaymentSchedule {
    protected $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function forLoan($loanId) {
        $stmt = $this->pdo->prepare("SELECT * FROM payment_schedules WHERE loan_id = :loan_id");
        $stmt->execute(['loan_id' => $loanId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data) {
        $sql = "INSERT INTO payment_schedules (loan_id, due_date, installment_number, principal_amount, interest_amount, total_amount, remaining_balance) 
                VALUES (:loan_id, :due_date, :installment_number, :principal_amount, :interest_amount, :total_amount, :remaining_balance)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }
}

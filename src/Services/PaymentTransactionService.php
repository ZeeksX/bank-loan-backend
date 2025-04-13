<?php
// File: src/Services/PaymentTransactionService.php

class PaymentTransactionService
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    public function getAllTransactions()
    {
        $stmt = $this->pdo->query("SELECT * FROM payment_transactions");
        return $stmt->fetchAll();
    }

    public function getTransactionById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM payment_transactions WHERE transaction_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getTransactionsByCustomerId($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM payment_transactions WHERE customer_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createTransaction(array $data)
    {
        // Generate transaction reference
        $transactionReference = uniqid('TXN-');

        // Validate loan exists
        $stmt = $this->pdo->prepare("SELECT loan_id FROM loans WHERE loan_id = :loan_id");
        $stmt->execute(['loan_id' => $data['loan_id']]);
        if (!$stmt->fetch()) {
            throw new Exception("loan_id does not exist: " . $data['loan_id']);
        }

        // Validate customer exists
        $stmt = $this->pdo->prepare("SELECT customer_id FROM customers WHERE customer_id = :customer_id");
        $stmt->execute(['customer_id' => $data['customer_id']]);
        if (!$stmt->fetch()) {
            throw new Exception("customer_id does not exist: " . $data['customer_id']);
        }

        // Get the next due schedule based on installment number
        $stmt = $this->pdo->prepare(
            "SELECT schedule_id, installment_number, principal_amount, interest_amount
             FROM payment_schedules 
             WHERE loan_id = :loan_id 
             AND status = 'pending'
             ORDER BY installment_number ASC
             LIMIT 1"
        );
        $stmt->execute(['loan_id' => $data['loan_id']]);
        $schedule = $stmt->fetch();

        if (!$schedule) {
            throw new Exception("No pending payment schedule found for loan: " . $data['loan_id']);
        }

        // Validate payment amount against schedule
        $stmt = $this->pdo->prepare(
            "SELECT total_amount 
             FROM payment_schedules 
             WHERE schedule_id = :schedule_id"
        );
        $stmt->execute(['schedule_id' => $schedule['schedule_id']]);
        $scheduleAmount = $stmt->fetchColumn();

        if ($data['amount_paid'] < $scheduleAmount) {
            throw new Exception("Payment amount is less than the scheduled amount of " . $scheduleAmount);
        }

        // Calculate principal and interest portions
        $principalPortion = $schedule['principal_amount'];
        $interestPortion = $schedule['interest_amount'];

        // Insert transaction
        $stmt = $this->pdo->prepare(
            "INSERT INTO payment_transactions (
            loan_id, schedule_id, customer_id, amount_paid, 
            principal_portion, interest_portion, payment_date, payment_method,
            transaction_reference, processed_by, status, notes
        ) VALUES (
            :loan_id, :schedule_id, :customer_id, :amount_paid,
            :principal_portion, :interest_portion, :payment_date, :payment_method,
            :transaction_reference, :processed_by, :status, :notes
        )"
        );

        $stmt->execute([
            'loan_id' => $data['loan_id'],
            'schedule_id' => $schedule['schedule_id'],
            'customer_id' => $data['customer_id'],
            'amount_paid' => $data['amount_paid'],
            'principal_portion' => $principalPortion,
            'interest_portion' => $interestPortion,
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'transaction_reference' => $transactionReference,
            'processed_by' => $data['processed_by'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'notes' => $data['notes'] ?? null
        ]);

        // Update schedule status
        $updateStmt = $this->pdo->prepare(
            "UPDATE payment_schedules 
         SET status = 'paid' 
         WHERE schedule_id = :schedule_id"
        );
        $updateStmt->execute(['schedule_id' => $schedule['schedule_id']]);

        return $this->pdo->lastInsertId();
    }

    public function updateTransaction($id, array $data)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE payment_transactions SET status = :status WHERE transaction_id = :id"
        );
        return $stmt->execute([
            'status' => $data['status'],
            'id' => $id
        ]);
    }

    public function deleteTransaction($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM payment_transactions WHERE transaction_id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

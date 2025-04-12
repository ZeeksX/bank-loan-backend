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

        // Determine the next schedule_id if not provided
        if (empty($data['schedule_id'])) {
            // Get the count of existing transactions for this loan to determine the next schedule_id
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) as count FROM payment_transactions WHERE loan_id = :loan_id"
            );
            $stmt->execute(['loan_id' => $data['loan_id']]);
            $result = $stmt->fetch();
            $data['schedule_id'] = $result['count'] + 1; // Next schedule_id
        }

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
            'schedule_id' => $data['schedule_id'],
            'customer_id' => $data['customer_id'],
            'amount_paid' => $data['amount_paid'],
            'principal_portion' => $data['principal_portion'],
            'interest_portion' => $data['interest_portion'],
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'transaction_reference' => $transactionReference,
            'processed_by' => $data['processed_by'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'notes' => $data['notes'] ?? null
        ]);

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

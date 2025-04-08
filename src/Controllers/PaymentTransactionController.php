<?php
// File: src/Controllers/PaymentTransactionController.php

class PaymentTransactionController
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    // GET /api/payment_transactions
    public function index()
    {
        $stmt = $this->pdo->query("SELECT * FROM payment_transactions");
        echo json_encode($stmt->fetchAll());
    }

    // GET /api/payment_transactions/{id}
    public function show($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM payment_transactions WHERE transaction_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode($stmt->fetch());
    }

    // POST /api/payment_transactions
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("INSERT INTO payment_transactions (loan_id, schedule_id, amount_paid, principal_portion, interest_portion, payment_date, payment_method, transaction_reference, processed_by, status, notes)
            VALUES (:loan_id, :schedule_id, :amount_paid, :principal_portion, :interest_portion, :payment_date, :payment_method, :transaction_reference, :processed_by, :status, :notes)");
        $stmt->execute([
            'loan_id' => $data['loan_id'],
            'schedule_id' => $data['schedule_id'],
            'amount_paid' => $data['amount_paid'],
            'principal_portion' => $data['principal_portion'],
            'interest_portion' => $data['interest_portion'],
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'transaction_reference' => $data['transaction_reference'] ?? null,
            'processed_by' => $data['processed_by'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'notes' => $data['notes'] ?? null
        ]);
        echo json_encode(['message' => 'Payment transaction recorded successfully']);
    }

    // PUT/PATCH /api/payment_transactions/{id}
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("UPDATE payment_transactions SET status = :status WHERE transaction_id = :id");
        $stmt->execute([
            'status' => $data['status'],
            'id' => $id
        ]);
        echo json_encode(['message' => 'Payment transaction updated successfully']);
    }

    // DELETE /api/payment_transactions/{id}
    public function destroy($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM payment_transactions WHERE transaction_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode(['message' => 'Payment transaction deleted successfully']);
    }
}

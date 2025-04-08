<?php
// File: src/Controllers/PaymentScheduleController.php

class PaymentScheduleController
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    // GET /api/payment_schedules
    public function index()
    {
        $stmt = $this->pdo->query("SELECT * FROM payment_schedules");
        echo json_encode($stmt->fetchAll());
    }

    // GET /api/payment_schedules/{id}
    public function show($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM payment_schedules WHERE schedule_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode($stmt->fetch());
    }

    // POST /api/payment_schedules
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("INSERT INTO payment_schedules (loan_id, due_date, installment_number, principal_amount, interest_amount, total_amount, remaining_balance, status)
            VALUES (:loan_id, :due_date, :installment_number, :principal_amount, :interest_amount, :total_amount, :remaining_balance, :status)");
        $stmt->execute([
            'loan_id' => $data['loan_id'],
            'due_date' => $data['due_date'],
            'installment_number' => $data['installment_number'],
            'principal_amount' => $data['principal_amount'],
            'interest_amount' => $data['interest_amount'],
            'total_amount' => $data['total_amount'],
            'remaining_balance' => $data['remaining_balance'],
            'status' => $data['status'] ?? 'pending'
        ]);
        echo json_encode(['message' => 'Payment schedule created successfully']);
    }

    // PUT/PATCH /api/payment_schedules/{id}
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("UPDATE payment_schedules SET status = :status WHERE schedule_id = :id");
        $stmt->execute([
            'status' => $data['status'],
            'id' => $id
        ]);
        echo json_encode(['message' => 'Payment schedule updated successfully']);
    }

    // DELETE /api/payment_schedules/{id}
    public function destroy($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM payment_schedules WHERE schedule_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode(['message' => 'Payment schedule deleted successfully']);
    }
}

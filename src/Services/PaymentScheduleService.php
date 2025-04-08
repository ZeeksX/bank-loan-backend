<?php
// File: src/Services/PaymentScheduleService.php

class PaymentScheduleService
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    public function getAllSchedules()
    {
        $stmt = $this->pdo->query("SELECT * FROM payment_schedules");
        return $stmt->fetchAll();
    }

    public function getScheduleById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM payment_schedules WHERE schedule_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function createSchedule(array $data)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO payment_schedules (loan_id, due_date, installment_number, principal_amount, interest_amount, total_amount, remaining_balance, status)
             VALUES (:loan_id, :due_date, :installment_number, :principal_amount, :interest_amount, :total_amount, :remaining_balance, :status)"
        );
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
        return $this->pdo->lastInsertId();
    }

    public function updateSchedule($id, array $data)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE payment_schedules SET status = :status WHERE schedule_id = :id"
        );
        return $stmt->execute([
            'status' => $data['status'],
            'id' => $id
        ]);
    }

    public function deleteSchedule($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM payment_schedules WHERE schedule_id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

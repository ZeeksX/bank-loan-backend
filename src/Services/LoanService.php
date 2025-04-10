<?php
// File: src/Services/LoanService.php

class LoanService
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    public function getAllLoans()
    {
        $stmt = $this->pdo->query("SELECT * FROM loans");
        return $stmt->fetchAll();
    }

    public function getLoanById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM loans WHERE loan_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function createLoan(array $data)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO loans 
             (application_id, customer_id, product_id, principal_amount, interest_rate, term, start_date, end_date, status, approved_by, approval_date)
             VALUES (:application_id, :customer_id, :product_id, :principal_amount, :interest_rate, :term, :start_date, :end_date, :status, :approved_by, :approval_date)"
        );
        $stmt->execute([
            'application_id' => $data['application_id'],
            'customer_id' => $data['customer_id'],
            'product_id' => $data['product_id'],
            'principal_amount' => $data['principal_amount'],
            'interest_rate' => $data['interest_rate'],
            'term' => $data['term'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => 'active',
            'approved_by' => $data['approved_by'],
            'approval_date' => date('Y-m-d H:i:s')
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateLoan($id, array $data)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE loans SET status = :status WHERE loan_id = :id"
        );
        return $stmt->execute([
            'status' => $data['status'],
            'id' => $id
        ]);
    }

    public function deleteLoan($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM loans WHERE loan_id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

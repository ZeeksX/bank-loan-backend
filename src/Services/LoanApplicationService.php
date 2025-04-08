<?php
// File: src/Services/LoanApplicationService.php

class LoanApplicationService
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    public function getAllLoanApplications()
    {
        $stmt = $this->pdo->query("SELECT * FROM loan_applications");
        return $stmt->fetchAll();
    }

    public function getLoanApplicationById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM loan_applications WHERE application_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function createLoanApplication(array $data)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO loan_applications (customer_id, product_id, requested_amount, requested_term, purpose, status)
            VALUES (:customer_id, :product_id, :requested_amount, :requested_term, :purpose, :status)"
        );
        $stmt->execute([
            'customer_id' => $data['customer_id'],
            'product_id' => $data['product_id'],
            'requested_amount' => $data['requested_amount'],
            'requested_term' => $data['requested_term'],
            'purpose' => $data['purpose'],
            'status' => 'submitted'
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateLoanApplication($id, array $data)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE loan_applications SET status = :status WHERE application_id = :id"
        );
        return $stmt->execute([
            'status' => $data['status'],
            'id' => $id
        ]);
    }

    public function deleteLoanApplication($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM loan_applications WHERE application_id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

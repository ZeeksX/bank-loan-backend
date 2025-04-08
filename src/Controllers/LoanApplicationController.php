<?php
// File: src/Controllers/LoanApplicationController.php

class LoanApplicationController
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    // GET /api/loan_applications
    public function index()
    {
        $stmt = $this->pdo->query("SELECT * FROM loan_applications");
        echo json_encode($stmt->fetchAll());
    }

    // GET /api/loan_applications/{id}
    public function show($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM loan_applications WHERE application_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode($stmt->fetch());
    }

    // POST /api/loan_applications
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("INSERT INTO loan_applications (customer_id, product_id, requested_amount, requested_term, purpose, status)
            VALUES (:customer_id, :product_id, :requested_amount, :requested_term, :purpose, :status)");
        $stmt->execute([
            'customer_id' => $data['customer_id'],
            'product_id' => $data['product_id'],
            'requested_amount' => $data['requested_amount'],
            'requested_term' => $data['requested_term'],
            'purpose' => $data['purpose'],
            'status' => 'submitted'
        ]);
        echo json_encode(['message' => 'Loan application submitted']);
    }

    // PUT/PATCH /api/loan_applications/{id}
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("UPDATE loan_applications SET status = :status WHERE application_id = :id");
        $stmt->execute([
            'status' => $data['status'],
            'id' => $id
        ]);
        echo json_encode(['message' => 'Loan application updated']);
    }

    // DELETE /api/loan_applications/{id}
    public function destroy($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM loan_applications WHERE application_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode(['message' => 'Loan application deleted']);
    }
}

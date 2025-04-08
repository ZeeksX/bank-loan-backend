<?php
// File: src/Controllers/LoanController.php

class LoanController
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    // GET /api/loans
    public function index()
    {
        $stmt = $this->pdo->query("SELECT * FROM loans");
        echo json_encode($stmt->fetchAll());
    }

    // GET /api/loans/{id}
    public function show($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM loans WHERE loan_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode($stmt->fetch());
    }

    // POST /api/loans
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("INSERT INTO loans (application_id, customer_id, product_id, principal_amount, interest_rate, term, start_date, end_date, status, approved_by, approval_date)
            VALUES (:application_id, :customer_id, :product_id, :principal_amount, :interest_rate, :term, :start_date, :end_date, :status, :approved_by, :approval_date)");
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
        echo json_encode(['message' => 'Loan created successfully']);
    }

    // PUT/PATCH /api/loans/{id}
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("UPDATE loans SET status = :status WHERE loan_id = :id");
        $stmt->execute([
            'status' => $data['status'],
            'id' => $id
        ]);
        echo json_encode(['message' => 'Loan updated successfully']);
    }

    // DELETE /api/loans/{id}
    public function destroy($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM loans WHERE loan_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode(['message' => 'Loan deleted successfully']);
    }
}

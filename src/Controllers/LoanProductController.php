<?php
// File: src/Controllers/LoanProductController.php

class LoanProductController
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    // GET /api/loan_products
    public function index()
    {
        $stmt = $this->pdo->query("SELECT * FROM loan_products");
        echo json_encode($stmt->fetchAll());
    }

    // GET /api/loan_products/{id}
    public function show($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM loan_products WHERE product_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode($stmt->fetch());
    }

    // POST /api/loan_products
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("INSERT INTO loan_products (product_name, description, interest_rate, min_term, max_term, min_amount, max_amount, requires_collateral, early_payment_fee, late_payment_fee, eligibility_criteria, is_active)
            VALUES (:product_name, :description, :interest_rate, :min_term, :max_term, :min_amount, :max_amount, :requires_collateral, :early_payment_fee, :late_payment_fee, :eligibility_criteria, :is_active)");
        $stmt->execute([
            'product_name' => $data['product_name'],
            'description' => $data['description'],
            'interest_rate' => $data['interest_rate'],
            'min_term' => $data['min_term'],
            'max_term' => $data['max_term'],
            'min_amount' => $data['min_amount'],
            'max_amount' => $data['max_amount'],
            'requires_collateral' => $data['requires_collateral'] ? 1 : 0,
            'early_payment_fee' => $data['early_payment_fee'] ?? 0,
            'late_payment_fee' => $data['late_payment_fee'] ?? 0,
            'eligibility_criteria' => $data['eligibility_criteria'],
            'is_active' => $data['is_active'] ?? 1
        ]);
        echo json_encode(['message' => 'Loan product created successfully']);
    }

    // PUT/PATCH /api/loan_products/{id}
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("UPDATE loan_products SET product_name = :product_name, interest_rate = :interest_rate WHERE product_id = :id");
        $stmt->execute([
            'product_name' => $data['product_name'],
            'interest_rate' => $data['interest_rate'],
            'id' => $id
        ]);
        echo json_encode(['message' => 'Loan product updated successfully']);
    }

    // DELETE /api/loan_products/{id}
    public function destroy($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM loan_products WHERE product_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode(['message' => 'Loan product deleted successfully']);
    }
}

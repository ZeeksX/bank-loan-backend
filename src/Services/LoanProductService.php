<?php
// File: src/Services/LoanProductService.php

class LoanProductService
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    public function getAllProducts()
    {
        $stmt = $this->pdo->query("SELECT * FROM loan_products");
        return $stmt->fetchAll();
    }

    public function getLoanProductById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM loan_products WHERE product_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createProduct(array $data)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO loan_products (product_name, description, interest_rate, min_term, max_term, min_amount, max_amount, requires_collateral, early_payment_fee, late_payment_fee, eligibility_criteria, is_active)
             VALUES (:product_name, :description, :interest_rate, :min_term, :max_term, :min_amount, :max_amount, :requires_collateral, :early_payment_fee, :late_payment_fee, :eligibility_criteria, :is_active)"
        );
        $stmt->execute([
            'product_name' => $data['product_name'],
            'description' => $data['description'],
            'interest_rate' => $data['interest_rate'],
            'min_term' => $data['min_term'],
            'max_term' => $data['max_term'],
            'min_amount' => $data['min_amount'],
            'max_amount' => $data['max_amount'],
            'requires_collateral' => !empty($data['requires_collateral']) ? 1 : 0,
            'early_payment_fee' => $data['early_payment_fee'] ?? 0,
            'late_payment_fee' => $data['late_payment_fee'] ?? 0,
            'eligibility_criteria' => $data['eligibility_criteria'],
            'is_active' => $data['is_active'] ?? 1
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateProduct($id, array $data)
    {
        // List of allowed updateable fields
        $allowedFields = [
            'product_name',
            'description',
            'interest_rate',
            'min_term',
            'max_term',
            'min_amount',
            'max_amount',
            'requires_collateral',
            'early_payment_fee',
            'late_payment_fee',
            'eligibility_criteria',
            'is_active'
        ];

        $updates = [];
        $params = ['id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                // Handle special cases
                switch ($field) {
                    case 'requires_collateral':
                    case 'is_active':
                        $params[$field] = !empty($data[$field]) ? 1 : 0;
                        break;
                    default:
                        $params[$field] = $data[$field];
                        break;
                }
                $updates[] = "$field = :$field";
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE loan_products SET " . implode(', ', $updates) . " WHERE product_id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteProduct($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM loan_products WHERE product_id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

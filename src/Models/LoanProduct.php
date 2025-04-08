<?php
namespace App\Models;

use PDO;

class LoanProduct {
    protected $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function all() {
        return $this->pdo->query("SELECT * FROM loan_products")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data) {
        $sql = "INSERT INTO loan_products (product_name, description, interest_rate, min_term, max_term, min_amount, max_amount, requires_collateral, early_payment_fee, late_payment_fee, eligibility_criteria) 
                VALUES (:product_name, :description, :interest_rate, :min_term, :max_term, :min_amount, :max_amount, :requires_collateral, :early_payment_fee, :late_payment_fee, :eligibility_criteria)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }
}

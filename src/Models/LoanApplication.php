<?php
namespace App\Models;

use PDO;

class LoanApplication
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO loan_applications (customer_id, product_id, requested_amount, requested_term, purpose) 
                VALUES (:customer_id, :product_id, :requested_amount, :requested_term, :purpose)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }

    public function all()
    {
        return $this->pdo->query("SELECT * FROM loan_applications")->fetchAll(PDO::FETCH_ASSOC);
    }
}

<?php
namespace App\Models;

use PDO;

class Customer
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function all()
    {
        $stmt = $this->pdo->query("SELECT * FROM customers");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO customers (first_name, last_name, date_of_birth, password, address, city, state, postal_code, country, phone, email, ssn, income, employment_status, credit_score) 
                VALUES (:first_name, :last_name, :date_of_birth, :password, :address, :city, :state, :postal_code, :country, :phone, :email, :ssn, :income, :employment_status, :credit_score)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }
}

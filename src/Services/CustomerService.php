<?php
// File: src/Services/CustomerService.php

namespace App\Services;

use App\Models\Database;
use PDO;

class CustomerService
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    public function getAllCustomers()
    {
        $stmt = $this->pdo->query("SELECT * FROM customers");
        return $stmt->fetchAll();
    }

    public function getCustomerById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE customer_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function createCustomer(array $data)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO customers 
            (first_name, last_name, email, date_of_birth, address, city, state, postal_code, country, phone, ssn, income, employment_status, credit_score) 
            VALUES 
            (:first_name, :last_name, :email, :date_of_birth, :address, :city, :state, :postal_code, :country, :phone, :ssn, :income, :employment_status, :credit_score)"
        );
        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'date_of_birth' => $data['date_of_birth'],
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'postal_code' => $data['postal_code'],
            'country' => $data['country'],
            'phone' => $data['phone'],
            'ssn' => $data['ssn'] ?? null,
            'income' => $data['income'] ?? null,
            'employment_status' => $data['employment_status'] ?? null,
            'credit_score' => $data['credit_score'] ?? null
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateCustomer($id, array $data)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE customers SET first_name = :first_name, last_name = :last_name, email = :email WHERE customer_id = :id"
        );
        return $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'id' => $id
        ]);
    }

    public function deleteCustomer($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM customers WHERE customer_id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

<?php
// File: src/Services/BankEmployeeService.php

class BankEmployeeService
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    public function getAllEmployees()
    {
        $stmt = $this->pdo->query("SELECT * FROM bank_employees");
        return $stmt->fetchAll();
    }

    public function getEmployeeById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM bank_employees WHERE employee_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function createEmployee(array $data)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO bank_employees (first_name, last_name, email, phone, department, position)
             VALUES (:first_name, :last_name, :email, :phone, :department, :position)"
        );
        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'department' => $data['department'],
            'position' => $data['position']
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateEmployee($id, array $data)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE bank_employees SET phone = :phone, department = :department, position = :position WHERE employee_id = :id"
        );
        return $stmt->execute([
            'phone' => $data['phone'],
            'department' => $data['department'],
            'position' => $data['position'],
            'id' => $id
        ]);
    }

    public function deleteEmployee($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM bank_employees WHERE employee_id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

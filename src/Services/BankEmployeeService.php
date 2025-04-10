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
        // Validate password strength (optional, but recommended)
        if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{6,}$/', $data['password'])) {
            http_response_code(400);
            echo json_encode(['message' => 'Password must contain at least 6 characters, one uppercase letter, one number, and one special character']);
            exit;
        }
    
        // Hash the password
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
    
        $stmt = $this->pdo->prepare(
            "INSERT INTO bank_employees (
                first_name, last_name, email, password,
                phone, department_id, role
            ) VALUES (
                :first_name, :last_name, :email, :password,
                :phone, :department_id, :role
            )"
        );
    
        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $hashedPassword, // Use the hashed password
            'phone' => $data['phone'],
            'department_id' => $data['department_id'],
            'role' => $data['role']
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

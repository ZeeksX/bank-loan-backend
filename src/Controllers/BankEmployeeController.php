<?php
// File: src/Controllers/BankEmployeeController.php

class BankEmployeeController
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    // GET /api/bank_employees
    public function index()
    {
        $stmt = $this->pdo->query("SELECT * FROM bank_employees");
        echo json_encode($stmt->fetchAll());
    }

    // GET /api/bank_employees/{id}
    public function show($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM bank_employees WHERE employee_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode($stmt->fetch());
    }

    // POST /api/bank_employees
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("INSERT INTO bank_employees (first_name, last_name, email, phone, department, position)
            VALUES (:first_name, :last_name, :email, :phone, :department, :position)");
        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'department' => $data['department'],
            'position' => $data['position']
        ]);
        echo json_encode(['message' => 'Bank employee created successfully']);
    }

    // PUT/PATCH /api/bank_employees/{id}
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("UPDATE bank_employees SET phone = :phone, department = :department, position = :position WHERE employee_id = :id");
        $stmt->execute([
            'phone' => $data['phone'],
            'department' => $data['department'],
            'position' => $data['position'],
            'id' => $id
        ]);
        echo json_encode(['message' => 'Bank employee updated successfully']);
    }

    // DELETE /api/bank_employees/{id}
    public function destroy($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM bank_employees WHERE employee_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode(['message' => 'Bank employee deleted successfully']);
    }
}

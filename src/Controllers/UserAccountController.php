<?php
// File: src/Controllers/UserAccountController.php

class UserAccountController
{
    protected $pdo;

    public function __construct() {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    // GET /api/user_accounts
    public function index() {
        $stmt = $this->pdo->query("SELECT * FROM user_accounts");
        echo json_encode($stmt->fetchAll());
    }

    // GET /api/user_accounts/{id}
    public function show($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM user_accounts WHERE user_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode($stmt->fetch());
    }

    // POST /api/user_accounts
    public function store() {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("INSERT INTO user_accounts (username, password_hash, role, customer_id, employee_id, is_active) VALUES (:username, :password_hash, :role, :customer_id, :employee_id, :is_active)");
        $stmt->execute([
            'username'      => $data['username'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role'          => $data['role'],
            'customer_id'   => $data['customer_id'] ?? null,
            'employee_id'   => $data['employee_id'] ?? null,
            'is_active'     => true
        ]);
        echo json_encode(['message' => 'User account created successfully']);
    }

    // PUT/PATCH /api/user_accounts/{id}
    public function update($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("UPDATE user_accounts SET username = :username, role = :role, is_active = :is_active WHERE user_id = :id");
        $stmt->execute([
            'username'  => $data['username'],
            'role'      => $data['role'],
            'is_active' => $data['is_active'],
            'id'        => $id
        ]);
        echo json_encode(['message' => 'User account updated successfully']);
    }

    // DELETE /api/user_accounts/{id}
    public function destroy($id) {
        $stmt = $this->pdo->prepare("DELETE FROM user_accounts WHERE user_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode(['message' => 'User account deleted successfully']);
    }
}

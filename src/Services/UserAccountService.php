<?php
// File: src/Services/UserAccountService.php

class UserAccountService
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    public function getAllUserAccounts()
    {
        $stmt = $this->pdo->query("SELECT * FROM user_accounts");
        return $stmt->fetchAll();
    }

    public function getUserAccountById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user_accounts WHERE user_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function createUserAccount(array $data)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO user_accounts (username, password_hash, role, customer_id, employee_id, is_active) 
            VALUES (:username, :password_hash, :role, :customer_id, :employee_id, :is_active)"
        );
        $stmt->execute([
            'username' => $data['username'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role' => $data['role'],
            'customer_id' => $data['customer_id'] ?? null,
            'employee_id' => $data['employee_id'] ?? null,
            'is_active' => true
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateUserAccount($id, array $data)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE user_accounts SET username = :username, role = :role, is_active = :is_active WHERE user_id = :id"
        );
        return $stmt->execute([
            'username' => $data['username'],
            'role' => $data['role'],
            'is_active' => $data['is_active'],
            'id' => $id
        ]);
    }

    public function deleteUserAccount($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM user_accounts WHERE user_id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

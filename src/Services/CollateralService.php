<?php
// File: src/Services/CollateralService.php

class CollateralService
{
    protected $pdo;

    public function __construct() {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    public function getAllCollaterals() {
        $stmt = $this->pdo->query("SELECT * FROM collaterals");
        return $stmt->fetchAll();
    }

    public function getCollateralById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM collaterals WHERE collateral_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function createCollateral(array $data) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO collaterals (customer_id, collateral_type, description, estimated_value, verification_status)
             VALUES (:customer_id, :collateral_type, :description, :estimated_value, :verification_status)"
        );
        $stmt->execute([
            'customer_id'         => $data['customer_id'],
            'collateral_type'     => $data['collateral_type'],
            'description'         => $data['description'],
            'estimated_value'     => $data['estimated_value'],
            'verification_status' => $data['verification_status'] ?? 'pending'
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateCollateral($id, array $data) {
        $stmt = $this->pdo->prepare(
            "UPDATE collaterals SET verification_status = :verification_status WHERE collateral_id = :id"
        );
        return $stmt->execute([
            'verification_status' => $data['verification_status'],
            'id' => $id
        ]);
    }

    public function deleteCollateral($id) {
        $stmt = $this->pdo->prepare("DELETE FROM collaterals WHERE collateral_id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

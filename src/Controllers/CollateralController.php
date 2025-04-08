<?php
// File: src/Controllers/CollateralController.php

class CollateralController
{
    protected $pdo;

    public function __construct() {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    // GET /api/collaterals
    public function index() {
        $stmt = $this->pdo->query("SELECT * FROM collaterals");
        echo json_encode($stmt->fetchAll());
    }

    // GET /api/collaterals/{id}
    public function show($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM collaterals WHERE collateral_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode($stmt->fetch());
    }

    // POST /api/collaterals
    public function store() {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("INSERT INTO collaterals (customer_id, collateral_type, description, estimated_value, verification_status)
            VALUES (:customer_id, :collateral_type, :description, :estimated_value, :verification_status)");
        $stmt->execute([
            'customer_id'        => $data['customer_id'],
            'collateral_type'    => $data['collateral_type'],
            'description'        => $data['description'],
            'estimated_value'    => $data['estimated_value'],
            'verification_status'=> $data['verification_status'] ?? 'pending'
        ]);
        echo json_encode(['message' => 'Collateral added successfully']);
    }

    // PUT/PATCH /api/collaterals/{id}
    public function update($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("UPDATE collaterals SET verification_status = :verification_status WHERE collateral_id = :id");
        $stmt->execute([
            'verification_status' => $data['verification_status'],
            'id' => $id
        ]);
        echo json_encode(['message' => 'Collateral updated successfully']);
    }

    // DELETE /api/collaterals/{id}
    public function destroy($id) {
        $stmt = $this->pdo->prepare("DELETE FROM collaterals WHERE collateral_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode(['message' => 'Collateral deleted successfully']);
    }
}


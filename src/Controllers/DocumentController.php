<?php
// File: src/Controllers/DocumentController.php

class DocumentController
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    // GET /api/documents
    public function index()
    {
        $stmt = $this->pdo->query("SELECT * FROM documents");
        echo json_encode($stmt->fetchAll());
    }

    // GET /api/documents/{id}
    public function show($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM documents WHERE document_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode($stmt->fetch());
    }

    // POST /api/documents
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("INSERT INTO documents (document_type, file_path, file_name, file_size, mime_type, verification_status, customer_id, application_id, loan_id, notes)
            VALUES (:document_type, :file_path, :file_name, :file_size, :mime_type, :verification_status, :customer_id, :application_id, :loan_id, :notes)");
        $stmt->execute([
            'document_type' => $data['document_type'],
            'file_path' => $data['file_path'],
            'file_name' => $data['file_name'],
            'file_size' => $data['file_size'],
            'mime_type' => $data['mime_type'],
            'verification_status' => $data['verification_status'] ?? 'pending',
            'customer_id' => $data['customer_id'] ?? null,
            'application_id' => $data['application_id'] ?? null,
            'loan_id' => $data['loan_id'] ?? null,
            'notes' => $data['notes'] ?? null
        ]);
        echo json_encode(['message' => 'Document uploaded successfully']);
    }

    // PUT/PATCH /api/documents/{id}
    public function update($id)
    {
        // Implement update logic as needed
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("UPDATE documents SET verification_status = :verification_status WHERE document_id = :id");
        $stmt->execute([
            'verification_status' => $data['verification_status'],
            'id' => $id
        ]);
        echo json_encode(['message' => 'Document updated successfully']);
    }

    // DELETE /api/documents/{id}
    public function destroy($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM documents WHERE document_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode(['message' => 'Document deleted successfully']);
    }
}

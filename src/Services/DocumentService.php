<?php
// File: src/Services/DocumentService.php

class DocumentService
{
    protected $pdo;

    public function __construct() {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    public function getAllDocuments() {
        $stmt = $this->pdo->query("SELECT * FROM documents");
        return $stmt->fetchAll();
    }

    public function getDocumentById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM documents WHERE document_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function createDocument(array $data) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO documents (document_type, file_path, file_name, file_size, mime_type, verification_status, customer_id, application_id, loan_id, notes)
             VALUES (:document_type, :file_path, :file_name, :file_size, :mime_type, :verification_status, :customer_id, :application_id, :loan_id, :notes)"
        );
        $stmt->execute([
            'document_type'       => $data['document_type'],
            'file_path'           => $data['file_path'],
            'file_name'           => $data['file_name'],
            'file_size'           => $data['file_size'],
            'mime_type'           => $data['mime_type'],
            'verification_status' => $data['verification_status'] ?? 'pending',
            'customer_id'         => $data['customer_id'] ?? null,
            'application_id'      => $data['application_id'] ?? null,
            'loan_id'             => $data['loan_id'] ?? null,
            'notes'               => $data['notes'] ?? null
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateDocument($id, array $data) {
        $stmt = $this->pdo->prepare(
            "UPDATE documents SET verification_status = :verification_status WHERE document_id = :id"
        );
        return $stmt->execute([
            'verification_status' => $data['verification_status'],
            'id' => $id
        ]);
    }

    public function deleteDocument($id) {
        $stmt = $this->pdo->prepare("DELETE FROM documents WHERE document_id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

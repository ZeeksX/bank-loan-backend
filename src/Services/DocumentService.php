<?php
// File: src/Services/DocumentService.php

class DocumentService
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    public function getAllDocuments()
    {
        $stmt = $this->pdo->query("SELECT document_id, document_type, file_name, file_size, mime_type, verification_status, loan_id, notes, created_at FROM documents");
        return $stmt->fetchAll();
    }

    public function getDocumentById($id)
    {
        $stmt = $this->pdo->prepare("SELECT document_id, document_type, file_name, file_size, mime_type, verification_status, loan_id, notes, created_at FROM documents WHERE document_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getDocumentsByCustomerId($customerId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT document_id, document_type, file_path, file_name, file_size, 
                       mime_type, verification_status, customer_id, notes, created_at 
                FROM documents 
                WHERE customer_id = :customer_id
                ORDER BY created_at DESC
            ");
            $stmt->execute(['customer_id' => $customerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getDocumentsByCustomerId: " . $e->getMessage());
            return [];
        }
    }

    public function handleDocumentUpload($customerId, $documentType, $uploadedFile)
    {
        try {
            $uploadDir = __DIR__ . '/../../uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $fileExtension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('doc_') . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;

            // Move uploaded file
            if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
                throw new Exception("Failed to move uploaded file");
            }

            // Insert into database
            $stmt = $this->pdo->prepare("
                INSERT INTO documents 
                (document_type, file_path, file_name, file_size, mime_type, customer_id, verification_status) 
                VALUES (:document_type, :file_path, :file_name, :file_size, :mime_type, :customer_id, 'pending')
            ");

            $stmt->execute([
                'document_type' => $documentType,
                'file_path' => `'/uploads/'` . $fileName,
                'file_name' => $uploadedFile['name'],
                'file_size' => $uploadedFile['size'],
                'mime_type' => $uploadedFile['type'],
                'customer_id' => $customerId
            ]);

            return [
                'document_id' => $this->pdo->lastInsertId(),
                'document_type' => $documentType,
                'file_name' => $uploadedFile['name']
            ];
        } catch (Exception $e) {
            error_log("Error in handleDocumentUpload: " . $e->getMessage());
            throw $e;
        }
    }
    public function createDocument(array $data)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO documents (document_type, file_path, file_name, file_size, mime_type, verification_status, loan_id, notes)
             VALUES (:document_type, :file_path, :file_name, :file_size, :mime_type, :verification_status, :loan_id, :notes)"
        );
        $stmt->execute([
            'document_type' => $data['document_type'],
            'file_path' => $data['file_path'],
            'file_name' => $data['file_name'],
            'file_size' => $data['file_size'],
            'mime_type' => $data['mime_type'],
            'verification_status' => $data['verification_status'] ?? 'pending',
            'customer_id' => $data['customer_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateDocument($id, array $data)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE documents SET verification_status = :verification_status WHERE document_id = :id"
        );
        return $stmt->execute([
            'verification_status' => $data['verification_status'],
            'id' => $id,
        ]);
    }

    public function deleteDocument($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM documents WHERE document_id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

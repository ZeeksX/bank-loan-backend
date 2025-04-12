<?php
// File: src/Services/DocumentService.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

class DocumentService
{
    protected $pdo;
    protected $mailerConfig;
    protected $allowedMimeTypes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    protected $maxFileSize = 10485760; // 10MB

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
        $this->mailerConfig = require __DIR__ . '/../../config/mailer.php';
    }

    public function getAllDocuments()
    {
        $stmt = $this->pdo->query("SELECT document_id, document_type, file_name, file_size, mime_type, verification_status, loan_id, notes, created_at FROM documents");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDocumentById($id)
    {
        $stmt = $this->pdo->prepare("SELECT document_id, document_type, file_name, file_size, mime_type, verification_status, loan_id, notes, created_at FROM documents WHERE document_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
            // Validate upload
            if (!isset($uploadedFile['tmp_name']) || !is_uploaded_file($uploadedFile['tmp_name'])) {
                throw new Exception("Invalid file upload");
            }

            // Validate file size
            if ($uploadedFile['size'] > $this->maxFileSize) {
                throw new Exception("File size exceeds the maximum limit of " . ($this->maxFileSize / 1048576) . "MB");
            }

            // Validate mime type
            if (!in_array($uploadedFile['type'], $this->allowedMimeTypes)) {
                throw new Exception("File type not allowed. Allowed types: PDF, JPEG, PNG, DOC, DOCX");
            }

            // Generate a secure filename
            $fileExtension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
            $secureFilename = uniqid('doc_') . '_' . time() . '.' . $fileExtension;

            // Store document record first
            $stmt = $this->pdo->prepare("
                INSERT INTO documents 
                (document_type, file_name, file_size, mime_type, customer_id, verification_status)
                VALUES (:type, :name, :size, :mime, :customer_id, 'pending')
            ");

            $stmt->execute([
                'type' => $documentType,
                'name' => $secureFilename,
                'size' => $uploadedFile['size'],
                'mime' => $uploadedFile['type'],
                'customer_id' => $customerId
            ]);

            $documentId = $this->pdo->lastInsertId();

            // Try to send email notification, but don't fail if email sending fails
            $this->sendEmailNotification($customerId, $documentType, $uploadedFile);

            return [
                'document_id' => $documentId,
                'document_type' => $documentType,
                'status' => 'pending'
            ];

        } catch (Exception $e) {
            error_log("Document Upload Error: " . $e->getMessage());
            throw new Exception("Document processing failed: " . $e->getMessage());
        }
    }

    /**
     * Send email notification about uploaded document
     * This method is separated to ensure document upload succeeds even if email fails
     */
    protected function sendEmailNotification($customerId, $documentType, $uploadedFile)
    {
        try {
            $mail = new PHPMailer(true);

            // Configure PHPMailer with exception handling for each step
            $mail->isSMTP();
            $mail->Host = $this->mailerConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->mailerConfig['username'];
            $mail->Password = $this->mailerConfig['password'];
            $mail->SMTPSecure = $this->mailerConfig['encryption'];
            $mail->Port = $this->mailerConfig['port'];
            $mail->SMTPDebug = 0; // Set to higher values for debugging
            $mail->Timeout = 30; // Set timeout to 30 seconds

            // Set to use HTML
            $mail->isHTML(true);

            // Validate and set from email and name
            if (empty($this->mailerConfig['from_email']) || !filter_var($this->mailerConfig['from_email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid sender email address");
            }

            $mail->setFrom($this->mailerConfig['from_email'], $this->mailerConfig['from_name'] ?? 'Document System');

            // Validate and set recipient email
            if (empty($this->mailerConfig['to_email']) || !filter_var($this->mailerConfig['to_email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid recipient email address");
            }

            $mail->addAddress($this->mailerConfig['to_email']);

            // Add attachment with sanitized filename
            $sanitizedFilename = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $uploadedFile['name']);
            $mail->addAttachment($uploadedFile['tmp_name'], $sanitizedFilename);

            // Set email content
            $mail->Subject = "New Document Upload: " . htmlspecialchars($documentType);
            $mail->Body = "
                <h3>New Document Uploaded</h3>
                <p><strong>Customer ID:</strong> " . htmlspecialchars($customerId) . "</p>
                <p><strong>Document Type:</strong> " . htmlspecialchars($documentType) . "</p>
                <p><strong>File Name:</strong> " . htmlspecialchars($sanitizedFilename) . "</p>
                <p><strong>Upload Time:</strong> " . date('Y-m-d H:i:s') . "</p>
            ";

            // Plain text alternative
            $mail->AltBody = "New Document Uploaded\n\n" .
                "Customer ID: " . $customerId . "\n" .
                "Document Type: " . $documentType . "\n" .
                "File Name: " . $sanitizedFilename . "\n" .
                "Upload Time: " . date('Y-m-d H:i:s');

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Log the error but don't throw an exception
            error_log("Email notification failed: " . $e->getMessage());
            return false;
        }
    }

    public function createDocument(array $data)
    {
        try {
            // Validate required fields
            $requiredFields = ['document_type', 'file_name', 'file_size', 'mime_type'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("Missing required field: {$field}");
                }
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO documents 
                (document_type, file_path, file_name, file_size, mime_type, verification_status, loan_id, customer_id, notes)
                VALUES (:document_type, :file_path, :file_name, :file_size, :mime_type, :verification_status, :loan_id, :customer_id, :notes)"
            );

            $stmt->execute([
                'document_type' => $data['document_type'],
                'file_path' => $data['file_path'] ?? null,
                'file_name' => $data['file_name'],
                'file_size' => $data['file_size'],
                'mime_type' => $data['mime_type'],
                'verification_status' => $data['verification_status'] ?? 'pending',
                'loan_id' => $data['loan_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database error in createDocument: " . $e->getMessage());
            throw new Exception("Failed to create document record");
        }
    }

    public function updateDocument($id, array $data)
    {
        try {
            // Only allow specific fields to be updated
            $allowedFields = ['verification_status', 'notes'];
            $updateFields = [];
            $params = ['id' => $id];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "{$field} = :{$field}";
                    $params[$field] = $data[$field];
                }
            }

            if (empty($updateFields)) {
                throw new Exception("No valid fields to update");
            }

            $updateQuery = "UPDATE documents SET " . implode(', ', $updateFields) . " WHERE document_id = :id";
            $stmt = $this->pdo->prepare($updateQuery);

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database error in updateDocument: " . $e->getMessage());
            throw new Exception("Failed to update document");
        }
    }

    public function deleteDocument($id)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM documents WHERE document_id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Database error in deleteDocument: " . $e->getMessage());
            throw new Exception("Failed to delete document");
        }
    }
}
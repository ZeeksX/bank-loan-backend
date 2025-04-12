<?php
// File: src/Controllers/DocumentController.php

require_once __DIR__ . '/../Services/DocumentService.php';

class DocumentController
{
    protected $documentService;

    public function __construct()
    {
        $this->documentService = new DocumentService();
    }

    // GET /api/documents
    public function index()
    {
        $documents = $this->documentService->getAllDocuments();
        echo json_encode($documents);
    }

    // GET /api/documents/customer/{customerId}
    public function getCustomerDocuments($customerId)
    {
        $documents = $this->documentService->getDocumentsByCustomerId($customerId);
        $formattedDocuments = $this->formatCustomerDocuments($documents);
        echo json_encode($formattedDocuments);
    }

    private function formatCustomerDocuments(array $documents): array
    {
        $formatted = [
            'id_verification' => [
                'id' => null,
                'type' => 'id_verification',
                'title' => 'ID Verification',
                'description' => 'Government-issued photo ID',
                'status' => 'required',
                'uploadedAt' => 'N/A',
            ],
            'proof_of_income' => [
                'id' => null,
                'type' => 'proof_of_income',
                'title' => 'Proof of Income',
                'description' => 'Recent pay stubs or tax returns',
                'status' => 'required',
                'uploadedAt' => 'N/A',
            ],
            'bank_statements' => [
                'id' => null,
                'type' => 'bank_statements',
                'title' => 'Bank Statements',
                'description' => 'Last 3 months of statements',
                'status' => 'required',
                'uploadedAt' => 'N/A',
            ],
            'employment_verification' => [
                'id' => null,
                'type' => 'employment_verification',
                'title' => 'Employment Verification',
                'description' => 'Letter from employer or contract',
                'status' => 'required',
                'uploadedAt' => 'N/A',
            ],
        ];

        foreach ($documents as $doc) {
            $type = $doc['document_type'];
            if (isset($formatted[$type])) {
                $formatted[$type]['id'] = $doc['document_id'];
                $formatted[$type]['status'] = $doc['verification_status'];
                $formatted[$type]['file_name'] = $doc['file_name'] ?? null;
                $formatted[$type]['file_size'] = $doc['file_size'] ?? null;
                $formatted[$type]['mime_type'] = $doc['mime_type'] ?? null;
                $formatted[$type]['uploadedAt'] = $doc['created_at'] ?? 'N/A';
            }
        }

        return array_values($formatted); 
    }

    // POST /api/documents/upload
    public function store()
    {
        $data = $_POST;
        $requiredFields = ['document_type', 'customer_id'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: $field"]);
                return;
            }
        }

        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'No file uploaded or upload error']);
            return;
        }

        $fileData = file_get_contents($_FILES['document']['tmp_name']);
        $fileName = $_FILES['document']['name'];
        $fileSize = $_FILES['document']['size'];
        $mimeType = $_FILES['document']['type'];

        $documentData = [
            'document_type' => $data['document_type'],
            'file_path' => $fileData,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'verification_status' => 'pending',
            'customer_id' => $data['customer_id'],
            'application_id' => $data['application_id'] ?? null,
            'loan_id' => $data['loan_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        $documentId = $this->documentService->createDocument($documentData);
        http_response_code(201);
        echo json_encode(['message' => 'Document uploaded successfully', 'document_id' => $documentId, 'document_type' => $data['document_type']]);
    }

    // PUT/PATCH /api/documents/{id}
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $this->documentService->updateDocument($id, $data);
        echo json_encode(['message' => 'Document updated successfully']);
    }

    // DELETE /api/documents/{id}
    public function destroy($id)
    {
        $this->documentService->deleteDocument($id);
        echo json_encode(['message' => 'Document deleted successfully']);
    }
}
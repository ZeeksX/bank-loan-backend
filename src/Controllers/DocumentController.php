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

    // GET /api/documents/customer/{customerId}
    public function getCustomerDocuments($customerId)
    {
        header('Content-Type: application/json');

        try {
            if (!is_numeric($customerId)) {
                throw new Exception("Invalid customer ID");
            }

            $documents = $this->documentService->getDocumentsByCustomerId($customerId);
            $formattedDocuments = $this->formatCustomerDocuments($documents);

            echo json_encode([
                'success' => true,
                'data' => $formattedDocuments
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function formatCustomerDocuments(array $documents): array
    {
        // Define default document types
        $defaultTypes = [
            'id_verification' => [
                'title' => 'ID Verification',
                'description' => 'Government-issued photo ID',
            ],
            'proof_of_income' => [
                'title' => 'Proof of Income',
                'description' => 'Recent pay stubs or tax returns',
            ],
            'bank_statements' => [
                'title' => 'Bank Statements',
                'description' => 'Last 3 months of statements',
            ],
            'employment_verification' => [
                'title' => 'Employment Verification',
                'description' => 'Letter from employer or contract',
            ]
        ];

        $result = [];

        // Initialize all document types with default status
        foreach ($defaultTypes as $type => $details) {
            $result[$type] = [
                'id' => null,
                'type' => $type,
                'title' => $details['title'],
                'description' => $details['description'],
                'status' => 'required',
                'uploadedAt' => null,
                'file_name' => null,
                'file_url' => null
            ];
        }

        // Update with actual documents
        foreach ($documents as $doc) {
            $type = $doc['document_type'];
            if (array_key_exists($type, $result)) {
                $result[$type] = [
                    'id' => $doc['document_id'],
                    'type' => $type,
                    'title' => $defaultTypes[$type]['title'] ?? ucfirst(str_replace('_', ' ', $type)),
                    'description' => $defaultTypes[$type]['description'] ?? '',
                    'status' => $doc['verification_status'] ?? 'pending',
                    'uploadedAt' => $doc['created_at'],
                    'file_name' => $doc['file_name'],
                    'file_url' => $doc['file_path']
                ];
            }
        }

        return array_values($result);
    }

    // POST /api/documents/upload
    public function store()
    {
        header('Content-Type: application/json');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method");
            }

            $data = $_POST;
            $requiredFields = ['document_type', 'customer_id'];

            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("No file uploaded or upload error");
            }

            $result = $this->documentService->handleDocumentUpload(
                $data['customer_id'],
                $data['document_type'],
                $_FILES['document']
            );

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'data' => $result
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
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
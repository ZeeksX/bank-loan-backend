<?php
// File: src/Controllers/LoanApplicationController.php

require_once __DIR__ . '/../Services/LoanApplicationService.php';

class LoanApplicationController
{
    protected $loanApplicationService;

    public function __construct()
    {
        $this->loanApplicationService = new LoanApplicationService();
    }

    // POST /api/loans/apply
    public function createLoanApplication()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (!$data) {
                throw new Exception('Invalid input data');
            }

            // Validate required fields
            $requiredFields = ['customer_id', 'product_id', 'requested_amount', 'requested_term', 'purpose', 'application_reference'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            $applicationId = $this->loanApplicationService->createLoanApplication($data);

            http_response_code(201);
            echo json_encode([
                'message' => 'Loan application submitted',
                'application_id' => $applicationId
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // GET /api/loans/customer/{id}
    public function getCustomerLoans($customerId)
    {
        try {
            $loans = $this->loanApplicationService->getCustomerLoans($customerId);

            header('Content-Type: application/json');
            echo json_encode($loans);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // GET /api/loans/application/{id}/status
    public function getLoanApplicationStatus($applicationId)
    {
        try {
            $status = $this->loanApplicationService->getLoanApplicationStatus($applicationId);

            if (!$status) {
                http_response_code(404);
                echo json_encode(['error' => 'Loan application not found']);
                return;
            }

            echo json_encode($status);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // GET /api/loans/applications
    public function getAllLoanApplications(): void
    {
        try {
            header('Content-Type: application/json');

            $loanApplications = $this->loanApplicationService->getAllLoanApplications();

            if (empty($loanApplications)) {
                http_response_code(200);
                echo json_encode([]);
                return;
            }

            // Define a mapping for status transformation
            $statusMap = [
                'submitted' => 'Pending',
                'under_review' => 'In Review',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'cancelled' => 'Cancelled'
            ];

            // Transform each loan application to match the desired JS object format
            $response = array_map(function ($application) use ($statusMap) {
                return [
                    'id' => $application['application_reference'] ?? null,
                    'customer' => trim(($application['first_name'] ?? '') . ' ' . ($application['last_name'] ?? '')),
                    'amount' => $application['requested_amount'] ?? null,
                    'type' => htmlspecialchars($application['purpose'] ?? ''),
                    // Map the database status to your custom labels
                    'status' => htmlspecialchars($statusMap[$application['status']] ?? $application['status']),
                    // Format the date as needed (Y-m-d format)
                    'date' => isset($application['application_date'])
                        ? date('Y-m-d', strtotime($application['application_date']))
                        : null,
                ];
            }, $loanApplications);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $response,
                'count' => count($response),
                'timestamp' => date('c')
            ]);

        } catch (Exception $e) {
            error_log('Error in getAllLoanApplications: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'An error occurred while retrieving loan applications',
                'message' => $e->getMessage(), // In development only, remove in production
                'timestamp' => date('c')
            ]);
        }
    }

}
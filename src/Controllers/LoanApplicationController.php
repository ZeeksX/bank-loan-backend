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

            if (empty($loans)) {
                echo json_encode([]);
                return;
            }

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
}
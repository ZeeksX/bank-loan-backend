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

    // GET /api/loan_applications
    public function index()
    {
        try {
            $applications = $this->loanApplicationService-> getAllLoanApplications();
            echo json_encode($applications);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to retrieve loan applications']);
        }
    }

    // GET /api/loan_applications/{id}
    public function show($id)
    {
        try {
            $application = $this->loanApplicationService->getLoanApplicationById($id);

            if ($application) {
                echo json_encode($application);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Loan application not found']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to retrieve loan application']);
        }
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
            $requiredFields = ['customer_id', 'product_id', 'requested_amount', 'requested_term', 'purpose'];
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

    // PUT/PATCH /api/loan_applications/{id}
    public function update($id)
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (!$data || !isset($data['status'])) {
                throw new Exception('Status is required');
            }

            $success = $this->loanApplicationService->updateLoanApplication($id, $data);

            if ($success) {
                echo json_encode(['message' => 'Loan application updated']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Loan application not found']);
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // DELETE /api/loan_applications/{id}
    public function destroy($id)
    {
        try {
            $success = $this->loanApplicationService->deleteLoanApplication($id);

            if ($success) {
                echo json_encode(['message' => 'Loan application deleted']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Loan application not found']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete loan application']);
        }
    }
}
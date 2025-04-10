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

    // PUT /api/loans/applications/{id}
    public function updateLoanApplication($applicationId)
    {
        try {
            AuthMiddleware::check(['admin', 'loan_officer', 'manager']); // Ensure only authorized roles can update

            $data = json_decode(file_get_contents("php://input"), true);

            if (!$data || !isset($data['status'])) {
                throw new Exception('Invalid or missing status in request data');
            }

            // Assuming you have a way to get the current logged-in employee's ID
            $loggedInEmployeeId = AuthMiddleware::getAuthenticatedUserId(); // Implement this in your AuthMiddleware

            $updated = $this->loanApplicationService->updateLoanApplication($applicationId, $data, $loggedInEmployeeId);

            if ($updated) {
                http_response_code(200);
                echo json_encode([
                    'message' => 'Loan application updated successfully',
                    'application_id' => $applicationId
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Loan application not found or no changes made']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // GET /api/loans/applications/id
    public function getLoanApplicationsById($id)
    {
        try {
            $loans = $this->loanApplicationService->getLoanApplicationById($id);

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

    // GET /api/loans/customer/{customerId}// GET /api/loans/customer/{customerId}
    public function getCustomerLoanApplications(int $customerId): void
    {
        try {
            AuthMiddleware::check(['customer']);
            $loanApplications = $this->loanApplicationService->getLoanApplicationsByCustomerId($customerId);

            header('Content-Type: application/json');

            $response = array_map(function ($application) {
                // Initialize variables to hold loan-related data
                $dueDate = null;
                $amountPaid = null;
                $nextPayment = null;

                // If the application has been approved (and thus has a loan_id), fetch loan details
                if ($application['status'] === 'approved') {
                    $loanDetails = $this->getLoanDetailsForApplication($application['application_id']);
                    if ($loanDetails) {
                        $dueDate = $loanDetails['due_date'];
                        $amountPaid = $loanDetails['amount_paid'];
                        $nextPayment = $loanDetails['next_payment'];
                    }
                }

                return [
                    'id' => $application['application_reference'] ?? null,
                    'amount' => $application['requested_amount'] ?? null,
                    'purpose' => htmlspecialchars($application['purpose'] ?? ''),
                    'status' => htmlspecialchars($application['status'] ?? ''),
                    'application_date' => isset($application['application_date'])
                        ? date('Y-m-d', strtotime($application['application_date']))
                        : null,
                    'due_date' => $dueDate ?? "N/A",
                    'amount_paid' => $amountPaid ?? "N/A",
                    'next_payment' => $nextPayment ?? "N/A",
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
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // Helper function to fetch loan details for a specific application
    private function getLoanDetailsForApplication(int $applicationId): ?array
    {
        try {
            $sql = "SELECT
                        loans.loan_id,
                        MAX(payment_schedules.due_date) AS due_date,
                        IFNULL(SUM(payment_transactions.amount_paid), 0) AS amount_paid,
                        (SELECT ps.total_amount
                         FROM payment_schedules ps
                         WHERE ps.loan_id = loans.loan_id AND ps.status = 'pending'
                         ORDER BY ps.due_date ASC
                         LIMIT 1) AS next_payment
                    FROM loans
                    LEFT JOIN payment_schedules ON loans.loan_id = payment_schedules.loan_id
                    LEFT JOIN payment_transactions ON loans.loan_id = payment_transactions.loan_id AND payment_transactions.status = 'completed'
                    WHERE loans.application_id = :applicationId
                    GROUP BY loans.loan_id";

            $stmt = $this->loanApplicationService->getPdo()->prepare($sql);
            $stmt->execute(['applicationId' => $applicationId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null; // Return null if no loan details found

        } catch (PDOException $e) {
            error_log("DB Error in getLoanDetailsForApplication: " . $e->getMessage());
            return null;
        }
    }
}
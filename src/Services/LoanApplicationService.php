<?php
// File: src/Services/LoanApplicationService.php

class LoanApplicationService
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    public function getAllLoanApplications()
    {
        // Adjusting the query to join with customers so we can fetch customer names.
        $sql = "SELECT la.*, c.first_name, c.last_name 
            FROM loan_applications la 
            JOIN customers c ON la.customer_id = c.customer_id";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getLoanApplicationById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM loan_applications WHERE application_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function createLoanApplication(array $data)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO loan_applications (customer_id, product_id, requested_amount, requested_term, purpose, status, application_reference)
            VALUES (:customer_id, :product_id, :requested_amount, :requested_term, :purpose, :status, :application_reference)"
        );
        $stmt->execute([
            'customer_id' => $data['customer_id'],
            'product_id' => $data['product_id'],
            'requested_amount' => $data['requested_amount'],
            'requested_term' => $data['requested_term'],
            'purpose' => $data['purpose'],
            'status' => 'submitted',
            'application_reference' => $data['application_reference']
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateLoanApplication($id, array $data)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE loan_applications SET status = :status WHERE application_id = :id"
        );
        return $stmt->execute([
            'status' => $data['status'],
            'id' => $id
        ]);
    }

    public function getCustomerLoans($customerId)
    {
        try {
            $stmt = $this->pdo->prepare(" SELECT * FROM customer_loans WHERE customer_id = :customer_id");
            $stmt->execute(['customer_id' => $customerId]);
            $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $statusMap = [
                'paid' => 'completed',
                'defaulted' => 'active',
                'cancelled' => 'rejected'
            ];

            $formattedLoans = [];
            foreach ($loans as $loan) {
                $formattedLoans[] = [
                    'id' => $loan['id'],
                    'name' => $loan['name'],
                    'amount' => $loan['amount'],
                    'amountPaid' => $loan['amountPaid'],
                    'dueDate' => $loan['status'] === 'paid' ? 'Completed' : $loan['dueDate'],
                    'nextPayment' => $loan['nextPayment'] === '₦0' ? 'N/A' : $loan['nextPayment'],
                    'progress' => (int) $loan['progress'],
                    'status' => $statusMap[$loan['status']] ?? $loan['status'],
                    'date' => $loan['date']
                ];
            }

            return $formattedLoans;
        } catch (PDOException $e) {
            error_log("DB Error in getCustomerLoans: " . $e->getMessage());
            return [];
        }
    }

    private function formatNaira($koboAmount)
    {
        // Divide by 100 to convert kobo to naira and format with no decimals.
        return '₦' . number_format($koboAmount / 100, 0);
    }

    private function formatDate($dateString)
    {
        return $dateString ? date("F j, Y", strtotime($dateString)) : "N/A";
    }

    public function getLoanApplicationStatus($applicationId)
    {
        // First check if the application exists
        $stmt = $this->pdo->prepare("
            SELECT 
                la.application_id, 
                la.application_reference,
                la.status as application_status,
                la.application_date,
                la.review_date,
                l.approval_date,
                l.loan_id,
                l.status as loan_status
            FROM loan_applications la
            LEFT JOIN loans l ON la.application_id = l.application_id
            WHERE la.application_id = :application_id
        ");

        $stmt->execute(['application_id' => $applicationId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        }

        // Create the status steps based on the application data
        $statusSteps = [];

        // Step 1: Application Submitted (always completed if the application exists)
        $statusSteps[] = [
            'id' => 1,
            'title' => 'Application Submitted',
            'description' => 'Your loan application has been received.',
            'completed' => true,
            'date' => date('m/d/Y', strtotime($result['application_date']))
        ];

        // Step 2: Verification Process
        $verificationCompleted = in_array($result['application_status'], ['under_review', 'approved', 'rejected']);
        $statusSteps[] = [
            'id' => 2,
            'title' => 'Verification Process',
            'description' => 'We are verifying your personal and financial information.',
            'completed' => $verificationCompleted,
            'isActive' => !$verificationCompleted,
            'date' => $verificationCompleted && $result['review_date'] ? date('m/d/Y', strtotime($result['review_date'])) : 'Pending'
        ];

        // Step 3: Loan Approval
        $approvalCompleted = in_array($result['application_status'], ['approved']) && !empty($result['loan_id']);
        $statusSteps[] = [
            'id' => 3,
            'title' => 'Loan Approval',
            'description' => 'Your loan is being reviewed for approval.',
            'completed' => $approvalCompleted,
            'isActive' => $verificationCompleted && !$approvalCompleted,
            'date' => $approvalCompleted && $result['approval_date'] ? date('m/d/Y', strtotime($result['approval_date'])) : 'Pending'
        ];

        // Step 4: Disbursement
        $disbursementCompleted = !empty($result['loan_id']) && $result['loan_status'] === 'active';
        $statusSteps[] = [
            'id' => 4,
            'title' => 'Disbursement',
            'description' => 'The approved loan amount will be disbursed to your account.',
            'completed' => $disbursementCompleted,
            'isActive' => $approvalCompleted && !$disbursementCompleted,
            'date' => $disbursementCompleted ? date('m/d/Y') : 'Pending'
        ];

        return $statusSteps;
    }

    public function deleteLoanApplication($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM loan_applications WHERE application_id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
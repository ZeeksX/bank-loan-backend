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
        $stmt = $this->pdo->query("SELECT * FROM loan_applications");
        return $stmt->fetchAll();
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
        $stmt = $this->pdo->prepare("
            SELECT 
                la.application_id,
                la.requested_amount,
                la.requested_term,
                la.purpose,
                la.application_reference,
                la.status as application_status,
                la.application_date,
                lp.name as product_name,
                l.loan_id,
                l.principal_amount,
                l.interest_rate,
                l.term,
                l.start_date,
                l.end_date,
                l.status as loan_status
            FROM loan_applications la
            LEFT JOIN loans l ON la.application_id = l.application_id
            JOIN loan_products lp ON la.product_id = lp.product_id
            WHERE la.customer_id = :customer_id
            ORDER BY la.application_date DESC
        ");

        $stmt->execute(['customer_id' => $customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
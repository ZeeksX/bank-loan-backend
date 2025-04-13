<?php
// File: src/Services/LoanApplicationService.php

class LoanApplicationService
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    public function getAllLoanApplications()
    {
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
        // Generate a random 8-digit code in the format LL-NNNN
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomLetters = $letters[rand(0, strlen($letters) - 1)] . $letters[rand(0, strlen($letters) - 1)];
        $randomNumber = sprintf('%04d', rand(0, 9999));
        $applicationReference = $randomLetters . '-' . $randomNumber;

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
            'application_reference' => $applicationReference
        ]);

        return [$this->pdo->lastInsertId(), $applicationReference];
    }

    public function updateLoanApplication($applicationId, array $data, $reviewedBy = null)
    {
        // Define allowed columns to prevent arbitrary updates
        $allowedColumns = ['status', 'reviewed_by', 'review_date'];

        $sqlParts = [];
        $params = [];

        // Add the application ID to the parameters for the WHERE clause
        $params['application_id'] = $applicationId;

        // Build the SET part of the SQL query dynamically
        foreach ($data as $key => $value) {
            // Check if the key (column name) is in the allowed columns list
            if (in_array($key, $allowedColumns)) {
                $sqlParts[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        // If there are no valid columns to update, return true (or false)
        if (empty($sqlParts)) {
            return true; // Or false, depending on your desired behavior
        }

        $sql = "UPDATE loan_applications SET " . implode(', ', $sqlParts) . " WHERE application_id = :application_id";

        $stmt = $this->pdo->prepare($sql);

        try {
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database error in updateLoanApplication: " . $e->getMessage());
            return false;
        }
    }

    public function getCustomerLoans($customerId)
    {
        try {
            $stmt = $this->pdo->prepare("
               SELECT
                la.application_reference AS id,
                lp.product_name AS name,
                l.loan_id AS loan_id,
                COALESCE(l.principal_amount, la.requested_amount) AS amount,
                (
                    SELECT IFNULL(SUM(pt2.amount_paid), 0)
                    FROM payment_transactions pt2 
                    WHERE pt2.loan_id = l.loan_id AND pt2.status = 'completed'
                ) AS amountPaid,
                CASE
                    WHEN l.loan_id IS NOT NULL THEN
                        CASE
                            WHEN MAX(ps.due_date) IS NOT NULL THEN MAX(ps.due_date)
                            ELSE DATE_ADD(l.start_date, INTERVAL l.term MONTH)
                        END
                    ELSE NULL
                END AS dueDate,
                CASE
                    WHEN l.loan_id IS NOT NULL THEN
                        (SELECT total_amount FROM payment_schedules WHERE loan_id = l.loan_id AND status = 'pending' ORDER BY due_date ASC LIMIT 1)
                    ELSE 0
                END AS nextPayment,
                ROUND(
                    IFNULL(
                        (
                            SELECT SUM(pt3.amount_paid)
                            FROM payment_transactions pt3
                            WHERE pt3.loan_id = l.loan_id AND pt3.status = 'completed'
                        ) / COALESCE(l.principal_amount, la.requested_amount) * 100
                    , 0)
                ) AS progress,
                CASE
                    WHEN l.loan_id IS NULL THEN la.status
                    WHEN la.status = 'under_review' THEN 'pending'
                    WHEN l.status = 'paid' THEN 'completed'
                    WHEN l.status = 'defaulted' THEN 'active' -- Assuming 'defaulted' means still active for display
                    WHEN la.status = 'rejected' OR la.status = 'cancelled' THEN 'rejected'
                    ELSE l.status
                END AS status,
                COALESCE(l.start_date, la.application_date) AS start_date
            FROM loan_applications la
            LEFT JOIN loans l ON la.application_id = l.application_id
            LEFT JOIN loan_products lp ON la.product_id = lp.product_id
            LEFT JOIN payment_schedules ps ON l.loan_id = ps.loan_id
            WHERE la.customer_id = :customer_id
            GROUP BY la.application_reference, lp.product_name, COALESCE(l.principal_amount, la.requested_amount),
                    l.loan_id, la.status, COALESCE(l.start_date, la.application_date)
            ORDER BY la.application_date DESC;
            ");
            $stmt->execute(['customer_id' => $customerId]);
            $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get payment history in a separate query to avoid GROUP_CONCAT issues
            $paymentHistoryStmt = $this->pdo->prepare("
                SELECT 
                    l.loan_id,
                    JSON_OBJECT(
                        'paymentDate', DATE_FORMAT(pt.payment_date, '%Y-%m-%d'),
                        'amountPaid', FORMAT(pt.amount_paid, 2),
                        'status', pt.status
                    ) AS payment_data
                FROM loans l
                JOIN payment_transactions pt ON l.loan_id = pt.loan_id
                JOIN loan_applications la ON l.application_id = la.application_id
                WHERE la.customer_id = :customer_id AND pt.status IN ('completed', 'pending', 'failed', 'reversed')
                ORDER BY pt.payment_date DESC
            ");
            $paymentHistoryStmt->execute(['customer_id' => $customerId]);
            $paymentRecords = $paymentHistoryStmt->fetchAll(PDO::FETCH_ASSOC);

            // Organize payment history by loan_id
            $paymentHistoryByLoan = [];
            foreach ($paymentRecords as $record) {
                if (!isset($paymentHistoryByLoan[$record['loan_id']])) {
                    $paymentHistoryByLoan[$record['loan_id']] = [];
                }
                $paymentHistoryByLoan[$record['loan_id']][] = json_decode($record['payment_data'], true);
            }

            $formattedLoans = [];
            foreach ($loans as $loan) {
                $formattedLoans[] = [
                    'id' => $loan['id'],
                    'name' => $loan['name'],
                    'amount' => $this->formatNaira($loan['amount']),
                    'amountPaid' => $this->formatNaira($loan['amountPaid']),
                    'dueDate' => $this->formatDate($loan['dueDate']),
                    'nextPayment' => $loan['nextPayment'] ? $this->formatNaira($loan['nextPayment']) : 'N/A',
                    'progress' => (int) $loan['progress'],
                    'status' => $loan['status'],
                    'start_date' => $this->formatDate($loan['start_date']),
                    'payment_history' => $loan['loan_id'] ? ($paymentHistoryByLoan[$loan['loan_id']] ?? []) : [],
                    'loan_id' => $loan['loan_id']
                ];
            }

            return $formattedLoans;
        } catch (PDOException $e) {
            error_log("DB Error in getCustomerLoans: " . $e->getMessage());
            return [];
        }
    }

    private function formatNaira($amount)
    {
        // Format with no decimals
        return '₦' . number_format($amount, 0);
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
        $disbursementCompleted = !empty($result['loan_id']) && in_array($result['loan_status'], ['active', 'paid', 'defaulted']);
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

    /**
     * Gets all loan applications for a specific customer.
     *
     * @param int $customerId The ID of the customer.
     * @return array An array of loan application data.
     */
    public function getLoanApplicationsByCustomerId(int $customerId): array
    {
        try {
            $sql = "SELECT la.*, c.first_name, c.last_name
                    FROM loan_applications la
                    JOIN customers c ON la.customer_id = c.customer_id
                    WHERE la.customer_id = :customer_id
                    ORDER BY la.application_date DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['customer_id' => $customerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error in getLoanApplicationsByCustomerId: " . $e->getMessage());
            return [];
        }
    }
}
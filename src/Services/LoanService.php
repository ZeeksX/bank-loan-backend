<?php
// File: src/Services/LoanService.php

class LoanService
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    public function getAllLoans()
    {
        $stmt = $this->pdo->query("SELECT * FROM loans");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLoanById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM loans WHERE loan_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getLoansByCustomerId($customerId)
    {
        $query = "SELECT * FROM loans WHERE customer_id = :id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createLoan(array $data)
    {
        $this->pdo->beginTransaction();

        try {
            // Create the loan
            $stmt = $this->pdo->prepare(
                "INSERT INTO loans
                (application_id, customer_id, product_id, principal_amount, interest_rate, term, start_date, end_date, status, approved_by, approval_date)
                VALUES (:application_id, :customer_id, :product_id, :principal_amount, :interest_rate, :term, :start_date, :end_date, :status, :approved_by, NOW())"
            );
            $stmt->execute([
                'application_id' => $data['application_id'],
                'customer_id' => $data['customer_id'],
                'product_id' => $data['product_id'],
                'principal_amount' => $data['principal_amount'],
                'interest_rate' => $data['interest_rate'],
                'term' => $data['term'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => 'active',
                'approved_by' => $data['approved_by']
            ]);

            $loanId = $this->pdo->lastInsertId();

            // Generate payment schedule
            $this->generatePaymentSchedule($loanId, $data['principal_amount'], $data['interest_rate'], $data['term'], $data['start_date']);

            $this->pdo->commit();

            return $loanId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    protected function generatePaymentSchedule($loanId, $principal, $interestRate, $term, $startDate)
    {
        $monthlyInterestRate = $interestRate / 100 / 12;
        $monthlyPayment = ($monthlyInterestRate > 0)
            ? $principal * $monthlyInterestRate * pow(1 + $monthlyInterestRate, $term) / (pow(1 + $monthlyInterestRate, $term) - 1)
            : $principal / $term;

        $remainingBalance = $principal;
        $startDate = new DateTime($startDate);

        for ($i = 1; $i <= $term; $i++) {
            $interestAmount = $remainingBalance * $monthlyInterestRate;
            $principalAmount = $monthlyPayment - $interestAmount;
            $remainingBalance -= $principalAmount;

            $dueDate = clone $startDate;
            $dueDate->add(new DateInterval('P' . $i . 'M'));

            $stmt = $this->pdo->prepare(
                "INSERT INTO payment_schedules
                (loan_id, due_date, installment_number, principal_amount, interest_amount, total_amount, remaining_balance, status)
                VALUES (:loan_id, :due_date, :installment_number, :principal_amount, :interest_amount, :total_amount, :remaining_balance, 'pending')"
            );

            $stmt->execute([
                'loan_id' => $loanId,
                'due_date' => $dueDate->format('Y-m-d'),
                'installment_number' => $i,
                'principal_amount' => $principalAmount,
                'interest_amount' => $interestAmount,
                'total_amount' => $monthlyPayment,
                'remaining_balance' => $remainingBalance
            ]);
        }
    }

    public function updateLoan($id, array $data)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE loans SET status = :status WHERE loan_id = :id"
        );
        return $stmt->execute([
            'status' => $data['status'],
            'id' => $id
        ]);
    }

    public function deleteLoan($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM loans WHERE loan_id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
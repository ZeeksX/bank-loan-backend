<?php
// File: src/Services/CustomerService.php


class CustomerService
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    public function getAllCustomers()
    {
        $stmt = $this->pdo->query("SELECT * FROM customers");
        return $stmt->fetchAll();
    }

    public function getCustomerById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE customer_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function createCustomer(array $data)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO customers 
            (first_name, last_name, email, date_of_birth, address, city, state, postal_code, country, phone, ssn, income, employment_status, credit_score) 
            VALUES 
            (:first_name, :last_name, :email, :date_of_birth, :address, :city, :state, :postal_code, :country, :phone, :ssn, :income, :employment_status, :credit_score)"
        );
        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'date_of_birth' => $data['date_of_birth'],
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'postal_code' => $data['postal_code'],
            'country' => $data['country'],
            'phone' => $data['phone'],
            'ssn' => $data['ssn'] ?? null,
            'income' => $data['income'] ?? null,
            'employment_status' => $data['employment_status'] ?? null,
            'credit_score' => $data['credit_score'] ?? null
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateCustomer($id, array $data)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE customers 
             SET first_name = :first_name, 
                 last_name = :last_name, 
                 email = :email,
                 income = :income,
                 account_number = :bankAccount,
                 bank = :bank,
                 employer = :employer,
                 occupation = :occupation
             WHERE customer_id = :id"
        );
        return $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'income' => $data['income'],
            'bankAccount' => $data['bankAccount'],
            'bank' => $data['bank'],
            'employer' => $data['employer'],
            'occupation' => $data['occupation'],
            'id' => $id
        ]);
    }

    public function deleteCustomer($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM customers WHERE customer_id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getCustomerLoanCount(int $customerId): array
    {
        $stmt = $this->pdo->prepare("SELECT status, COUNT(*) as count FROM customer_loans WHERE customer_id = :customer_id GROUP BY status");
        $stmt->execute(['customer_id' => $customerId]);
        $loanCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Fetch as key-value pairs

        $totalLoans = array_sum($loanCounts);

        return [
            'total' => $totalLoans,
            'submitted' => $loanCounts['submitted'] ?? 0,
            'pending' => $loanCounts['pending'] ?? $loanCounts['under_review'] ?? 0,
            'active' => $loanCounts['active'] ?? 0,
            'paid' => $loanCounts['paid'] ?? 0,
            'defaulted' => $loanCounts['defaulted'] ?? 0,
            'cancelled' => $loanCounts['cancelled'] ?? 0,
        ];
    }

    public function getAllCustomersLoanCounts(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                c.customer_id,
                c.first_name,
                c.last_name,
                c.email,
                c.created_at,
                c.id_verification_status as status,
                COUNT(l.loan_id) as total,
                SUM(CASE WHEN l.status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN l.status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN l.status = 'paid' THEN 1 ELSE 0 END) as paid,
                SUM(CASE WHEN l.status = 'defaulted' THEN 1 ELSE 0 END) as defaulted,
                SUM(CASE WHEN l.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM customers c
            LEFT JOIN loans l ON c.customer_id = l.customer_id
            GROUP BY c.customer_id, c.first_name, c.last_name
        ");

        $customerLoanCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $customerLoanCounts;
    }
}
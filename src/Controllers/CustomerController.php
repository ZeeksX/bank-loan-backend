<?php
// File: src/Controllers/CustomerController.php

class CustomerController
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    // GET /api/customers
    public function index()
    {
        $stmt = $this->pdo->query("SELECT * FROM customers");
        $customers = $stmt->fetchAll();
        echo json_encode($customers);
    }

    // GET /api/customers/{id}
    public function show($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE customer_id = :id");
        $stmt->execute(['id' => $id]);
        $customer = $stmt->fetch();
        if ($customer) {
            echo json_encode($customer);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Customer not found']);
        }
    }

    // GET /api/customers/all
    public function getAllCustomers()
    {
        $stmt = $this->pdo->query("SELECT * FROM customers");
        $customers = $stmt->fetchAll();
        echo json_encode($customers);
    }

    // POST /api/customers
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        // Basic validation should be added
        $stmt = $this->pdo->prepare("INSERT INTO customers (first_name, last_name, email, date_of_birth, address, city, state, postal_code, country, phone, ssn, income, employment_status, credit_score)
            VALUES (:first_name, :last_name, :email, :date_of_birth, :address, :city, :state, :postal_code, :country, :phone, :ssn, :income, :employment_status, :credit_score)");
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
        echo json_encode(['message' => 'Customer created successfully']);
    }

    // PUT/PATCH /api/customers/{id}
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->pdo->prepare("UPDATE customers SET first_name = :first_name, last_name = :last_name, email = :email WHERE customer_id = :id");
        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'id' => $id
        ]);
        echo json_encode(['message' => 'Customer updated successfully']);
    }

    // DELETE /api/customers/{id}
    public function destroy($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM customers WHERE customer_id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode(['message' => 'Customer deleted successfully']);
    }
}

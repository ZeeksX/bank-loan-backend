<?php
// File: src/Controllers/CustomerController.php

require_once __DIR__ . '/../Services/CustomerService.php';

class CustomerController
{
    protected $customerService;

    public function __construct()
    {
        $this->customerService = new CustomerService();
    }

    // GET /api/customers
    public function index()
    {
        $customers = $this->customerService->getAllCustomers();
        echo json_encode($customers);
    }

    // GET /api/customers/{id}
    public function show($id)
    {
        $customer = $this->customerService->getCustomerById($id);
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
        $customers = $this->customerService->getAllCustomersLoanCounts();
        echo json_encode($customers);
    }

    // GET /api/customers/{id}/details-with-loans
    public function getCustomerDetailsWithLoanCounts(int $customerId)
    {
        $customer = $this->customerService->getCustomerById($customerId);
        if ($customer) {
            $loanCounts = $this->customerService->getCustomerLoanCount($customerId);
            $response = [
                'customer' => $customer,
                'loanCounts' => $loanCounts,
            ];
            echo json_encode($response);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Customer not found']);
        }
    }

    // POST /api/customers
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        // Basic validation should be added
        $customerId = $this->customerService->createCustomer($data);
        echo json_encode(['message' => 'Customer created successfully', 'customer_id' => $customerId]);
    }

    // PUT/PATCH /api/customers/{id}
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $this->customerService->updateCustomer($id, $data);
        echo json_encode(['message' => 'Customer updated successfully']);
    }

    // DELETE /api/customers/{id}
    public function destroy($id)
    {
        $this->customerService->deleteCustomer($id);
        echo json_encode(['message' => 'Customer deleted successfully']);
    }
}
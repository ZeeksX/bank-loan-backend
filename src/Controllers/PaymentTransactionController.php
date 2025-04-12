<?php

// File: src/Controllers/PaymentTransactionController.php

require_once __DIR__ . '/../Services/PaymentTransactionService.php';

class PaymentTransactionController
{
    protected $service;

    public function __construct()
    {
        $this->service = new PaymentTransactionService();
    }

    // GET /api/payment_transactions
    public function index()
    {
        echo json_encode($this->service->getAllTransactions());
    }

    // GET /api/payment_transactions/{id}
    public function show($id)
    {
        echo json_encode($this->service->getTransactionById($id));
    }

    // POST /api/payment-transaction
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $this->service->createTransaction($data);
        echo json_encode(['message' => 'Payment transaction recorded successfully', 'id' => $id]);
    }

    // PUT/PATCH /api/payment_transactions/{id}
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $this->service->updateTransaction($id, $data);
        echo json_encode(['message' => 'Payment transaction updated successfully']);
    }

    // DELETE /api/payment_transactions/{id}
    public function destroy($id)
    {
        $this->service->deleteTransaction($id);
        echo json_encode(['message' => 'Payment transaction deleted successfully']);
    }

    // GET /api/customers/{customerId}/payments
    public function getAllPaymentTransactionsByCustomerId($customerId)
    {
        try {
            $transactions = $this->service->getTransactionsByCustomerId($customerId);
            if ($transactions === false) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error']);
                return;
            }
            echo json_encode($transactions);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

}
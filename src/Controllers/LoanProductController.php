<?php
// File: src/Controllers/LoanProductController.php

require_once __DIR__ . '/../Services/LoanProductService.php';

class LoanProductController
{
    protected $loanProductService;

    public function __construct()
    {
        $this->loanProductService = new LoanProductService();
    }

    // GET /api/loans/products
    public function getAllLoanProducts()
    {
        $products = $this->loanProductService->getAllProducts();
        echo json_encode($products);
    }

    // GET /api/loans/products/{id}
    public function show($id)
    {
        $product = $this->loanProductService->getLoanProductById($id);
        echo json_encode($product);
    }

    // POST /api/loans/products
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $productId = $this->loanProductService->createProduct($data);
        echo json_encode(['message' => 'Loan product created successfully', 'product_id' => $productId]);
    }

    // PUT/PATCH /api/loans/products/{id}
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $success = $this->loanProductService->updateProduct($id, $data);

        if ($success) {
            echo json_encode(['message' => 'Loan product updated successfully']);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Failed to update loan product']);
        }
    }

    // DELETE /api/loans/products/{id}
    public function destroy($id)
    {
        $success = $this->loanProductService->deleteProduct($id);

        if ($success) {
            echo json_encode(['message' => 'Loan product deleted successfully']);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Failed to delete loan product']);
        }
    }
}

<?php
// File: src/Controllers/LoanController.php

require_once __DIR__ . '/../Services/LoanService.php';

class LoanController
{
    protected $loanService;

    public function __construct()
    {
        $this->loanService = new LoanService();
    }

    // GET /api/loans
    public function index()
    {
        $loans = $this->loanService->getAllLoans();
        echo json_encode($loans);
    }

    // GET /api/loans/{id}
    public function show($id)
    {
        $loan = $this->loanService->getLoanById($id);
        echo json_encode($loan);
    }

    // POST /api/loans
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // You might want to add field validation here before creating the loan

        $loanId = $this->loanService->createLoan($data);
        echo json_encode([
            'message' => 'Loan created successfully',
            'loan_id' => $loanId
        ]);
    }

    // PUT/PATCH /api/loans/{id}
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $updated = $this->loanService->updateLoan($id, $data);
        
        if ($updated) {
            echo json_encode(['message' => 'Loan updated successfully']);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Failed to update loan']);
        }
    }

    // DELETE /api/loans/{id}
    public function destroy($id)
    {
        $deleted = $this->loanService->deleteLoan($id);
        
        if ($deleted) {
            echo json_encode(['message' => 'Loan deleted successfully']);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Failed to delete loan']);
        }
    }
}

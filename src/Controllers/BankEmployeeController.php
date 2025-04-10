<?php
// File: src/Controllers/BankEmployeeController.php

require_once __DIR__ . '/../Services/BankEmployeeService.php';

class BankEmployeeController
{
    protected $service;

    public function __construct()
    {
        $this->service = new BankEmployeeService();
    }

    // GET /api/bank_employees
    public function index()
    {
        $employees = $this->service->getAllEmployees();
        echo json_encode($employees);
    }

    // GET /api/bank_employees/{id}
    public function show($id)
    {
        $employee = $this->service->getEmployeeById($id);
        echo json_encode($employee);
    }

    // POST /api/bank-employees
    public function createEmployee(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents("php://input"), true);

            $requiredFields = [
                'first_name',
                'last_name',
                'email',
                'phone',
                'department_id',
                'role',
                'password'
            ];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            $employeeId = $this->service->createEmployee($data);

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Employee created successfully',
                'employee_id' => $employeeId
            ]);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // PUT/PATCH /api/bank_employees/{id}
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $this->service->updateEmployee($id, $data);
        echo json_encode(['message' => 'Bank employee updated successfully']);
    }

    // DELETE /api/bank_employees/{id}
    public function destroy($id)
    {
        $this->service->deleteEmployee($id);
        echo json_encode(['message' => 'Bank employee deleted successfully']);
    }
}

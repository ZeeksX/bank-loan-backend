<?php
// File: config/routes.php

require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/BankEmployeeController.php';
require_once __DIR__ . '/../src/Controllers/CustomerController.php';
require_once __DIR__ . '/../src/Controllers/LoanApplicationController.php';
require_once __DIR__ . '/../src/Controllers/LoanController.php';
require_once __DIR__ . '/../src/Controllers/LoanProductController.php';
require_once __DIR__ . '/../src/Controllers/PaymentScheduleController.php';
require_once __DIR__ . '/../src/Middleware/AuthMiddleware.php';

// Set CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

switch (true) {
    // GET /
    case $requestUri === '/' && $requestMethod === 'GET':
        echo json_encode(['message' => 'Welcome to the Loan Management System API']);
        break;

    // POST /api/auth/login
    case $requestUri === '/api/auth/login' && $requestMethod === 'POST':
        $controller = new AuthController();
        $controller->login();
        break;

    // POST /api/refresh
    case $requestUri === '/api/refresh' && $requestMethod === 'POST':
        $controller = new AuthController();
        $controller->refreshToken();
        break;

    // POST /api/auth/register
    case $requestUri === '/api/auth/register' && $requestMethod === 'POST':
        $controller = new AuthController();
        $controller->register();
        break;

    // POST /api/bank-employee
    case $requestUri === '/api/bank-employee' && $requestMethod === 'POST':
        $controller = new BankEmployeeController();
        $controller->createEmployee();
        break;

    // GET /api/customers/{id}/loans
    case preg_match('#^/api/customers/(\d+)/loans$#', $requestUri, $matches) && $requestMethod === 'GET':
        AuthMiddleware::check(['admin', 'loan_officer', 'manager', 'customer']);
        $controller = new LoanController();
        $controller->getCustomerLoans($matches[1]);
        break;

    // GET /api/customers/{id}
    case preg_match('#^/api/customers/(\d+)$#', $requestUri, $matches) && $requestMethod === 'GET':
        AuthMiddleware::check(['admin', 'loan_officer', 'manager']);
        $controller = new CustomerController();
        $controller->show($matches[1]);
        break;

    // GET /api/customers/all
    case $requestUri === '/api/customers/all' && $requestMethod === 'GET':
        AuthMiddleware::check(['admin', 'loan_officer', 'manager']);
        $controller = new CustomerController();
        $controller->getAllCustomers();
        break;

    // GET /api/loans
    case $requestUri === '/api/loans' && $requestMethod === 'GET':
        AuthMiddleware::check(['admin', 'loan_officer', 'manager']);
        $controller = new LoanController();
        $controller->index();
        break;

    // POST /api/loans/apply
    case $requestUri === '/api/loans/apply' && $requestMethod === 'POST':
        AuthMiddleware::check(['customer']);
        $controller = new LoanApplicationController();
        $controller->createLoanApplication();
        break;

    // GET /api/loans/application/{id}/status
    case preg_match('#^/api/loans/application/(\d+)/status$#', $requestUri, $matches) && $requestMethod === 'GET':
        AuthMiddleware::check(['customer']);
        $controller = new LoanApplicationController();
        $controller->getLoanApplicationStatus($matches[1]);
        break;

    // GET /api/loans/applications
    case $requestUri === '/api/loans/applications' && $requestMethod === 'GET':
        AuthMiddleware::check(['admin', 'loan_officer', 'manager']);
        $controller = new LoanApplicationController();
        $controller->getAllLoanApplications();
        break;

    // GET /api/loans/applications/{id}
    case preg_match('#^/api/loans/applications/(\d+)$#', $requestUri, $matches) && $requestMethod === 'GET':
        AuthMiddleware::check(['customer']);
        $controller = new LoanApplicationController();
        $controller->getLoanApplicationsById($matches[1]);
        break;

    // PUT /api/loans/applications/{id}
    case preg_match('#^/api/loans/applications/(\d+)$#', $requestUri, $matches) && $requestMethod === 'PUT':
        AuthMiddleware::check(['admin', 'loan_officer', 'manager']);
        $controller = new LoanApplicationController();
        $controller->updateLoanApplication($matches[1]);
        break;

    // GET /api/loans/customer/{id}
    case preg_match('#^/api/loans/customer/(\d+)$#', $requestUri, $matches) && $requestMethod === 'GET':
        AuthMiddleware::check(['customer']);
        $controller = new LoanApplicationController();
        $controller->getCustomerLoanApplications($matches[1]);
        break;

    // GET /api/loans/products
    case $requestUri === '/api/loans/products' && $requestMethod === 'GET':
        $controller = new LoanProductController();
        $controller->getAllLoanProducts();
        break;

    default:
        http_response_code(404);
        echo json_encode(['message' => 'Route not found']);
        break;
}
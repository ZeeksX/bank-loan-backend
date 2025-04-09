<?php
// File: config/routes.php

require_once __DIR__ . '/../src/Controllers/CustomerController.php';
require_once __DIR__ . '/../src/Controllers/LoanController.php';
require_once __DIR__ . '/../src/Controllers/LoanProductController.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
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
    // Root route
    case $requestUri === '/' && $requestMethod === 'GET':
        echo json_encode(['message' => 'Welcome to the Loan Management System API']);
        break;

    // Register a new user    
    case $requestUri === '/api/auth/register' && $requestMethod === 'POST':
        $controller = new AuthController();
        $controller->register();
        break;

    // Login a user
    case $requestUri === '/api/auth/login' && $requestMethod === 'POST':
        $controller = new AuthController();
        $controller->login();
        break;

    // Refresh the access token
    case $requestUri === '/api/refresh' && $requestMethod === 'POST':
        $controller = new AuthController();
        $controller->refreshToken();
        break;

    // Get all customers
    case $requestUri === '/api/customers/all' && $requestMethod === 'GET':
        AuthMiddleware::check('admin');
        $controller = new CustomerController();
        $controller->getAllCustomers();
        break;

    //Get specific customer
    case preg_match('#^/api/customers/(\d+)$#', $requestUri, $matches) && $requestMethod === 'GET':
        AuthMiddleware::check('admin');
        $controller = new CustomerController();
        $controller->show($matches[1]);
        break;

    // Get all loan products
    case $requestUri === '/api/loans/products' && $requestMethod === 'GET':
        $controller = new LoanProductController();
        $controller->getAllLoanProducts();
        break;

    default:
        http_response_code(404);
        echo json_encode(['message' => 'Route not found']);
        break;
}

<?php
// File: public/index.php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
}

// Set headers
header('Content-Type: application/json');

// Health check endpoint
if ($_SERVER['REQUEST_URI'] === '/health') {
    echo json_encode(['status' => 'API is working! Server is running.', 'timestamp' => date('Y-m-d H:i:s')]);
    exit;
}

// Load app config and routes
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/routes.php';
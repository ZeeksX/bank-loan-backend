<?php
// File: public/index.php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables only if the file exists
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    Dotenv\Dotenv::createImmutable(dirname($envPath))->safeLoad();
}

// Set headers
header('Content-Type: application/json');

// Load app config and routes
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/routes.php';

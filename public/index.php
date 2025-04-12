<?php
// File: public/index.php

// Autoload dependencies first
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Set headers
header('Content-Type: application/json');

// Load app config and routes
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/routes.php';

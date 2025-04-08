<?php
// File: public/index.php

// Optionally, set the content type as JSON if you expect to always return JSON responses.
header('Content-Type: application/json');

// Autoload dependencies and load app configuration (optional)
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/app.php';

// Require your routes which handle the incoming requests
require_once __DIR__ . '/../config/routes.php';

<?php
// File: src/Middleware/AuthMiddleware.php

require_once __DIR__ . '/../Helpers/JWTHandler.php';

class AuthMiddleware
{
    public static function check($requiredRole = null)
    {
        $headers = getallheaders();

        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Authorization header missing']);
            exit;
        }

        $authHeader = $headers['Authorization'];
        $token = str_replace('Bearer ', '', $authHeader);

        $decoded = JWTHandler::validateToken($token);
        if (!$decoded) {
            http_response_code(401);
            echo json_encode(['message' => 'Invalid or expired token']);
            exit;
        }

        if ($requiredRole && $decoded->role !== $requiredRole) {
            http_response_code(403);
            echo json_encode(['message' => 'Access denied: Insufficient permissions']);
            exit;
        }

        return ['user_id' => $decoded->sub, 'role' => $decoded->role];
    }
}
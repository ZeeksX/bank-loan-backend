<?php
// File: src/Middleware/AuthMiddleware.php

require_once __DIR__ . '/../Helpers/JWTHandler.php';

class AuthMiddleware
{
    public static function check(array $allowedRoles = [])
    {
        try {
            // Improved Header Retrieval
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

            // Log Headers for Debugging
            error_log("All Server Headers: " . print_r($_SERVER, true));
            error_log("Authorization Header: " . $authHeader);

            // Check if authorization header exists
            if (empty($authHeader)) {
                throw new Exception('Authorization header missing', 401);
            }

            // Extract token
            if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                throw new Exception('Invalid authorization header format', 401);
            }
            $token = $matches[1];

            // Log the Token
            error_log("Extracted Token: " . $token);

            // Validate token
            $decoded = JWTHandler::validateToken($token);
            if (!$decoded) {
                throw new Exception('Invalid or expired token', 401);
            }

            // Log Decoded Token Payload
            error_log("Decoded Token Payload: " . print_r($decoded, true));

            // Check if token has required claims
            if (!isset($decoded->sub) || !isset($decoded->role)) {
                throw new Exception('Malformed token payload', 401);
            }

            // Check role permission if required
            if (!empty($allowedRoles) && !in_array($decoded->role, $allowedRoles)) {
                throw new Exception('Insufficient permissions', 403);
            }

            return [
                'user_id' => $decoded->sub,
                'role' => $decoded->role,
                'token' => $token
            ];

        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 401);
            echo json_encode([
                'message' => $e->getMessage(),
                'error' => true,
                'code' => $e->getCode() ?: 401
            ]);
            exit;
        }
    }

    // Helper method to get authenticated user ID
    public static function getAuthenticatedUserId()
    {
        try {
            $auth = self::check();
            return $auth['user_id'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    // Helper method to get authenticated user role
    public static function getAuthenticatedUserRole()
    {
        try {
            $auth = self::check();
            return $auth['role'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    // New method to get full authenticated user data
    public static function getAuthenticatedUser()
    {
        try {
            return self::check();
        } catch (Exception $e) {
            return null;
        }
    }
}
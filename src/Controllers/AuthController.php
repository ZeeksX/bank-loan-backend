<?php
// File: src/Controllers/AuthController.php

require_once __DIR__ . '/../Helpers/JWTHandler.php';

class AuthController
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    // POST /api/register
    public function register()
    {
        // ... (registration logic remains unchanged) ...
    }

    // POST /api/login
    public function login()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['email'], $data['password'])) {
            http_response_code(400);
            echo json_encode(['message' => 'Email and password required']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE email = :email");
        $stmt->execute(['email' => $data['email']]);
        $customer = $stmt->fetch();

        if (!$customer || !password_verify($data['password'], $customer['password'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Invalid credentials']);
            return;
        }

        // Generate tokens with correct parameters:
        // Access token: valid for 1 hour, with "type" set to 'access'
        $accessToken = JWTHandler::generateToken(
            $customer['customer_id'],
            'customer',
            3600,
            ['type' => 'access']
        );

        // Refresh token: valid for 7 days, with "type" set to 'refresh'
        $refreshToken = JWTHandler::generateToken(
            $customer['customer_id'],
            'customer',
            86400 * 7,
            ['type' => 'refresh']
        );

        $this->storeRefreshToken($customer['customer_id'], $refreshToken);

        echo json_encode([
            'message' => 'Login successful',
            'tokens' => [
                'access' => $accessToken,
                'refresh' => $refreshToken
            ],
            'customer' => [
                'customerId' => $customer['customer_id'],
                'first_name' => $customer['first_name'],
                'last_name' => $customer['last_name'],
                'email' => $customer['email'],
                'role' => 'customer',
            ]
        ]);
    }

    private function storeRefreshToken($customerId, $refreshToken)
    {
        $stmt = $this->pdo->prepare("DELETE FROM refresh_tokens WHERE customer_id = :customer_id");
        $stmt->execute(['customer_id' => $customerId]);

        $stmt = $this->pdo->prepare("
INSERT INTO refresh_tokens (customer_id, token, expires_at)
VALUES (:customer_id, :token, DATE_ADD(NOW(), INTERVAL 7 DAY))
");
        $stmt->execute([
            'customer_id' => $customerId,
            'token' => $refreshToken
        ]);
    }

    public function refreshToken()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['refresh'])) {
            http_response_code(400);
            echo json_encode(['message' => 'Refresh token required']);
            return;
        }

        try {
            $decoded = JWTHandler::validateToken($data['refresh']);
            if (!$decoded || $decoded->type !== 'refresh') {
                throw new Exception('Invalid token type');
            }

            $stmt = $this->pdo->prepare("
SELECT c.customer_id
FROM refresh_tokens rt
JOIN customers c ON rt.customer_id = c.customer_id
WHERE rt.token = :token AND rt.expires_at > NOW()
");
            $stmt->execute(['token' => $data['refresh']]);
            $tokenData = $stmt->fetch();

            if (!$tokenData) {
                throw new Exception('Invalid or expired refresh token');
            }

            // Generate a new access token
            $accessToken = JWTHandler::generateToken(
                $tokenData['customer_id'],
                'customer',
                3600,
                ['type' => 'access']
            );

            echo json_encode([
                'message' => 'Token refreshed successfully',
                'access_token' => $accessToken
            ]);
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(['message' => $e->getMessage()]);
        }
    }
}
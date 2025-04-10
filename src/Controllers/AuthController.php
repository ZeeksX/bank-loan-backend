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
        $data = json_decode(file_get_contents("php://input"), true);

        if (
            !isset(
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['password'],
            $data['date_of_birth'],
            $data['address'],
            $data['city'],
            $data['state'],
            $data['postal_code'],
            $data['country'],
            $data['phone']
        )
        ) {
            http_response_code(400);
            echo json_encode(['message' => 'Missing required fields']);
            exit;
        }

        // Validate password strength
        if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{6,}$/', $data['password'])) {
            http_response_code(400);
            echo json_encode(['message' => 'Password must contain at least 6 characters, one uppercase letter, one number, and one special character']);
            exit;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE email = :email");
        $stmt->execute(['email' => $data['email']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['message' => 'Email already registered']);
            return;
        }

        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

        $stmt = $this->pdo->prepare("
        INSERT INTO customers (
            first_name, last_name, email, password, date_of_birth,
            address, city, state, postal_code, country, phone,
            ssn, income, employment_status, credit_score, id_verification_status
        ) VALUES (
            :first_name, :last_name, :email, :password, :date_of_birth,
            :address, :city, :state, :postal_code, :country, :phone,
            :ssn, :income, :employment_status, :credit_score, 'Pending'
        )
    ");

        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'date_of_birth' => $data['date_of_birth'],
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'postal_code' => $data['postal_code'],
            'country' => $data['country'],
            'phone' => $data['phone'],
            'ssn' => $data['ssn'] ?? null,
            'income' => $data['income'] ?? null,
            'employment_status' => $data['employment_status'] ?? null,
            'credit_score' => $data['credit_score'] ?? null
        ]);

        $customerId = $this->pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'message' => 'Customer registered successfully',
            'customer_id' => $customerId
        ]);
    }

    // POST /api/login
    public function login()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        error_log("Login request data: " . print_r($data, true));

        if (!isset($data['email'], $data['password'])) {
            error_log("Missing email or password in request");
            http_response_code(400);
            echo json_encode(['message' => 'Email and password required']);
            return;
        }

        try {
            // Check customer
            $stmt = $this->pdo->prepare("SELECT customer_id, first_name, last_name, email, password FROM customers WHERE email = :email");
            $stmt->execute(['email' => $data['email']]);
            $customer = $stmt->fetch();

            if ($customer) {
                error_log("Found customer: " . print_r($customer, true));

                if (!password_verify($data['password'], $customer['password'])) {
                    error_log("Password verification failed for customer");
                    http_response_code(401);
                    echo json_encode(['message' => 'Invalid credentials']);
                    return;
                }

                error_log("Customer authentication successful");

                // Generate tokens
                $accessToken = JWTHandler::generateToken(
                    $customer['customer_id'],
                    'customer',
                    3600,
                    ['type' => 'access']
                );

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
                    'user' => [
                        'userId' => $customer['customer_id'],
                        'first_name' => $customer['first_name'],
                        'last_name' => $customer['last_name'],
                        'email' => $customer['email'],
                        'role' => 'customer',
                    ]
                ]);
                return;
            }

            // Check employee
            $stmt = $this->pdo->prepare("SELECT employee_id, first_name, last_name, email, password, role FROM bank_employees WHERE email = :email");
            $stmt->execute(['email' => $data['email']]);
            $employee = $stmt->fetch();

            if ($employee) {
                error_log("Found employee: " . print_r($employee, true));

                if (!password_verify($data['password'], $employee['password'])) {
                    error_log("Password verification failed for employee");
                    http_response_code(401);
                    echo json_encode(['message' => 'invalid credentials']);
                    return;
                }

                error_log("Employee authentication successful");

                // Generate tokens
                $accessToken = JWTHandler::generateToken(
                    $employee['employee_id'],
                    $employee['role'],
                    3600,
                    ['type' => 'access']
                );

                $refreshToken = JWTHandler::generateToken(
                    $employee['employee_id'],
                    $employee['role'],
                    86400 * 7,
                    ['type' => 'refresh']
                );

                $this->storeRefreshToken($employee['employee_id'], $refreshToken);

                echo json_encode([
                    'message' => 'Login successful',
                    'tokens' => [
                        'access' => $accessToken,
                        'refresh' => $refreshToken
                    ],
                    'user' => [
                        'userId' => $employee['employee_id'],
                        'first_name' => $employee['first_name'],
                        'last_name' => $employee['last_name'],
                        'email' => $employee['email'],
                        'role' => $employee['role'],
                    ]
                ]);
                return;
            }

            error_log("No user found with email: " . $data['email']);
            http_response_code(401);
            echo json_encode(['message' => 'Invalid credentials']);

        } catch (PDOException $e) {
            error_log("Database error during login: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['message' => 'Internal server error']);
        }
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
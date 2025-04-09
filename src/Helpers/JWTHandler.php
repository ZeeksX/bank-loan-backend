<?php
// File: src/Helpers/JWTHandler.php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHandler
{
    private static $secret;

    public static function init()
    {
        // Initialize your environment variables. Adjust the path as needed.
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        self::$secret = $_ENV['JWT_SECRET'];
    }

    /**
     * Generates a JWT token.
     *
     * @param array $payload The custom payload claims.
     * @param int   $expire  Expiration time in seconds.
     *
     * @return string The encoded JWT token.
     */
    public static function generateToken($payload, $expire)
    {
        self::init();
        $issuedAt = time();
        $tokenPayload = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $issuedAt + $expire,
            'iss' => 'http://localhost'
        ]);

        return JWT::encode($tokenPayload, self::$secret, 'HS256');
    }

    /**
     * Validates a JWT token.
     *
     * @param string $token The JWT token.
     *
     * @return mixed The decoded token if valid, or false on failure.
     */
    public static function validateToken($token)
    {
        self::init();
        try {
            $decoded = JWT::decode($token, new Key(self::$secret, 'HS256'));
            return $decoded;
        } catch (Exception $e) {
            return false;
        }
    }
}

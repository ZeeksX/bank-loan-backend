<?php
// File: src/Helpers/JWTHandler.php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHandler
{
    private static $secret;
    private static $expire;

    public static function init()
    {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        self::$secret = $_ENV['JWT_SECRET'];
        self::$expire = $_ENV['JWT_EXPIRE_TIME'] ?? 3600;
    }

    public static function generateToken($userId, $role)
    {
        self::init();
        $payload = [
            'iss' => 'http://localhost',
            'iat' => time(),
            'exp' => time() + self::$expire,
            'sub' => $userId,
            'role' => $role
        ];

        return JWT::encode($payload, self::$secret, 'HS256');
    }

    public static function validateToken($token)
    {
        self::init();

        try {
            $decoded = JWT::decode($token, new Key(self::$secret, 'HS256'));
            return $decoded; // Return the full decoded object
        } catch (Exception $e) {
            return false;
        }
    }

}

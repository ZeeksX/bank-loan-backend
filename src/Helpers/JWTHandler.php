<?php
// File: src/Helpers/JWTHandler.php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

class JWTHandler
{
    private static $secret;
    private static $initialized = false;

    public static function init()
    {
        if (!self::$initialized) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
            $dotenv->required('JWT_SECRET')->notEmpty();
            self::$secret = $_ENV['JWT_SECRET'];
            self::$initialized = true;
        }
    }

    /**
     * Generates a JWT token.
     *
     * @param int|string $userId      The user identifier
     * @param string     $userRole    The user role (e.g., 'customer')
     * @param int        $expire      Expiration time in seconds (default: 1 hour)
     * @param array      $customClaims Additional custom claims (e.g., ['type' => 'access'])
     *
     * @return string The encoded JWT token
     * @throws Exception If secret key is not configured
     */
    public static function generateToken($userId, $userRole, $expire = 3600, array $customClaims = [])
    {
        self::init();

        if (empty(self::$secret)) {
            throw new Exception('JWT secret key not configured');
        }

        $issuedAt = time();
        $tokenPayload = array_merge($customClaims, [
            'iat' => $issuedAt,
            'exp' => $issuedAt + $expire,
            'iss' => $_ENV['APP_URL'] ?? 'http://localhost',
            'sub' => (string) $userId,
            'role' => $userRole,
        ]);

        return JWT::encode($tokenPayload, self::$secret, 'HS256');
    }

    /**
     * Validates a JWT token.
     *
     * @param string $token The JWT token to validate
     *
     * @return object|false The decoded token payload if valid, false on failure
     */
    public static function validateToken($token)
    {
        self::init();

        if (empty(self::$secret)) {
            return false;
        }

        try {
            $decoded = JWT::decode($token, new Key(self::$secret, 'HS256'));
            return $decoded;
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            error_log('JWT Signature Invalid: ' . $e->getMessage());
        } catch (\Firebase\JWT\BeforeValidException $e) {
            error_log('JWT Token Not Yet Valid: ' . $e->getMessage());
        } catch (\Firebase\JWT\ExpiredException $e) {
            error_log('JWT Token Expired: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log('JWT Validation Error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Refreshes an existing token (extends expiration)
     *
     * @param string $token  The existing JWT token
     * @param int    $expire New expiration time in seconds
     *
     * @return string|false The new token or false on failure
     */
    public static function refreshToken($token, $expire = 3600)
    {
        $payload = self::validateToken($token);
        if (!$payload) {
            return false;
        }

        return self::generateToken(
            $payload->sub,
            $payload->role,
            $expire,
            (array) $payload
        );
    }
}

<?php

namespace PS\Core\Api\Authmethodes;

use Config;
use PS\Core\Api\Request;

class BearerToken implements AuthMethodeInterface
{
    private const TOKEN_BODY = [
        'UserID' => null,
        'username' => null,
        'firstname' => null,
        'lastname' => null,
        'mail' => null,
        'exp' => null
    ];

    private readonly ?array $token;

    public function __construct($login = false)
    {
        if (!$login) {
            $this->token = self::parseToken();
        }
    }

    public function getUser(): int
    {
        return 1;
    }

    public function getLoggedIn(): bool
    {
        return isset($this->token['UserID']);
    }

    public function login(Request $request): ?array
    {
        if (!isset($request->parameters['username'])) {
            throw new \Exception('Username has to be set.');
        }
        if (!isset($request->parameters['password'])) {
            throw new \Exception('Password has to be set.');
        }
        $paramaters = $request->parameters;
        return ['token' => self::createToken()];
    }

    private static function parseToken(): ?array
    {
        $token = self::getBearerToken();
        if ($token === null) {
            throw new \Exception("Cannot get JWT Token");
        }
        return self::validateToken($token);
    }

    public static function createToken(): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $dataArray = self::TOKEN_BODY;
        $dataArray['UserID'] = 1;
        $dataArray['username'] = null;
        $dataArray['firstname'] = null;
        $dataArray['lastname'] = null;
        $dataArray['mail'] = null;
        $dataArray['exp'] = time() + Config::TOKEN_EXPIRED_IN_S;
        $payload = json_encode(
            $dataArray
        );

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::generateSecret(), true);
        $base64UrlSignature = self::base64url_encode($signature);
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
        return $jwt;
    }

    public static final function validateToken($jwt): ?array
    {
        list($header, $payload, $signatureProvided) = explode('.', $jwt);

        // Decode Header and Payload
        $decodedHeader = base64_decode($header);
        $decodedPayload = base64_decode($payload);

        // Check Token Expiration
        $expiration = json_decode($decodedPayload)->exp;
        $tokenExpired = is_null($expiration) ? true : ($expiration - time()) < 0;

        // Base64URL-Encode Header/Payload
        $base6UrlHeader = self::base64url_encode($decodedHeader);
        $base64UrlPayload = self::base64url_encode($decodedPayload);

        // Signature
        $signature = hash_hmac('SHA256', $base6UrlHeader . "." . $base64UrlPayload, self::generateSecret(), true);
        $base64UrlSignature = self::base64url_encode($signature);
        $signatureValid = ($base64UrlSignature === $signatureProvided);

        // decode payload
        $arrPayload = json_decode($decodedPayload, true);

        if ($signatureValid && (!$tokenExpired || is_null(Config::TOKEN_EXPIRED_IN_S))) {
            return $arrPayload;
        } else {
            return null;
        }
    }

    private static function base64url_encode($str)
    {
        return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
    }

    private static function generateSecret(): string
    {
        $serverName = $_SERVER['SERVER_NAME'];
        $serverAddr = $_SERVER['SERVER_ADDR'];
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        $data = $serverName . $serverAddr . $documentRoot;
        $secret = hash('sha256', $data);
        return $secret;
    }

    private static function getAuthorizationHeader(): ?string
    {
        if (isset($_SERVER['Authorization'])) {
            return trim($_SERVER['Authorization']);
        }

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return trim($_SERVER['HTTP_AUTHORIZATION']);
        }

        if (function_exists('apache_request_headers')) {
            $requestHeaders = array_change_key_case(apache_request_headers(), CASE_LOWER);
            if (isset($requestHeaders['authorization'])) {
                return trim($requestHeaders['authorization']);
            }
        }
        return null;
    }

    private static function getBearerToken(): ?string
    {
        $headers = self::getAuthorizationHeader();
        if (!empty($headers) && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
        return null;
    }
}

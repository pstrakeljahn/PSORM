<?php

namespace PS\Core\Api\Authmethodes;

use PS\Core\Api\Request;
use Object\Session;
use Object\User;
use ObjectPeer\SessionPeer;
use ObjectPeer\UserPeer;
use PS\Core\Database\Criteria;
use PS\Core\Helper\Env;
use PS\Core\Logging\Logging;

/**
 * Class BearerToken
 *
 * Handles authentication via JWT tokens.
 */
class BearerToken implements AuthMethodeInterface
{
    /** @var array Default token structure */
    private const TOKEN_BODY = [
        'UserID'    => null,
        'username'  => null,
        'firstname' => null,
        'lastname'  => null,
        'mail'      => null,
        'exp'       => null,
    ];

    /** @var array|null The decoded JWT payload */
    private ?array $token = null;

    /** @var Request Current request object */
    private Request $request;

    /**
     * BearerToken constructor.
     *
     * @param bool $login Whether this is a login attempt or not.
     * @throws \Exception
     */
    public function __construct(bool $login = false)
    {
        $this->request = Request::getInstance();

        if (!$login) {
            $this->token = self::parseToken($this->request);
        }
    }

    /**
     * Returns the authenticated user.
     *
     * @return User|null
     * @throws \Exception
     */
    public function getUser(): ?User
    {
        $users = UserPeer::find(
            Criteria::getInstance()
                ->add(UserPeer::USERNAME, $this->token['username'])
                ->addLimit(0, 1)
        );

        if (empty($users)) {
            throw new \Exception('Invalid credentials.');
        }

        return $users[0];
    }

    /**
     * Checks if user is authenticated.
     *
     * @return bool
     */
    public function getLoggedIn(): bool
    {
        return isset($this->token['UserID']);
    }

    /**
     * Performs login and returns JWTs.
     *
     * @return array|null
     * @throws \Exception
     */
    public function login(): ?array
    {
        if ($this->request->httpMethod !== 'POST') {
            throw new \Exception('Use POST method to login.');
        }

        $username = $this->request->parameters['username'] ?? null;
        $password = $this->request->parameters['password'] ?? null;

        if (!$username || !$password) {
            throw new \Exception('Username and password must be set.');
        }

        $user = self::checkPassword([
            'username' => $username,
            'password' => $password,
        ]);

        if (!$user) {
            throw new \Exception('Invalid credentials.');
        }

        $token = self::createToken($user);
        $refreshToken = self::createRefreshToken($user);
        $this->createSession($refreshToken, $user);

        return ['token' => $token, 'refreshToken' => $refreshToken];
    }

    /**
     * Logs out by deleting the user's session.
     *
     * @return array
     * @throws \Exception
     */
    public function logout(): array
    {
        if ($this->request->httpMethod !== 'POST') {
            throw new \Exception('Use POST method to logout.');
        }

        $sessions = SessionPeer::find(
            Criteria::getInstance()->add(SessionPeer::USERID, $this->token['UserID'])
        );

        foreach ($sessions as $session) {
            $session->delete();
        }

        return [];
    }

    /**
     * Refreshes an access token using a valid refresh token.
     *
     * @return array|null
     * @throws \Exception
     */
    public function refresh(): ?array
    {
        if (!in_array($this->request->httpMethod, ['POST', 'OPTIONS'])) {
            throw new \Exception('Use POST method to refresh.');
        }

        $refreshToken = $this->request->parameters['refreshToken'] ?? null;
        if (!$refreshToken) {
            throw new \Exception('Refresh token is missing.');
        }

        $sessions = SessionPeer::find(
            Criteria::getInstance()
                ->add(SessionPeer::USERID, $this->token['UserID'])
                ->add(SessionPeer::REFRESHTOKEN, $refreshToken)
        );

        if (empty($sessions)) {
            throw new \Exception('No active session found.');
        }

        $user = $this->getUser();
        $newToken = self::createToken($user);
        $newRefreshToken = self::createRefreshToken($user);
        $this->createSession($newRefreshToken, $user);

        return ['token' => $newToken, 'refreshToken' => $newRefreshToken];
    }

    /**
     * Verifies user credentials and password.
     *
     * @param array $params
     * @return User|null
     * @throws \Exception
     */
    private static function checkPassword(array $params): ?User
    {
        $users = UserPeer::find(
            Criteria::getInstance()
                ->add(UserPeer::USERNAME, $params['username'])
                ->addLimit(0, 1)
        );

        if (empty($users)) {
            throw new \Exception('Invalid credentials.');
        }

        $user = $users[0];
        if (password_verify($params['password'], $user->getPassword())) {
            return $user;
        }

        return null;
    }

    /**
     * Creates a user session in the DB.
     *
     * @param string $refreshToken
     * @param User $user
     * @return void
     */
    private function createSession(string $refreshToken, User $user): void
    {
        $sessions = SessionPeer::find(
            Criteria::getInstance()->add(SessionPeer::USERID, $user->getID())
        );

        $session = $sessions[0] ?? new Session();
        $session
            ->setUserid($user->getID())
            ->setRefreshtoken($refreshToken)
            ->save();
    }

    /**
     * Parses the JWT from the Authorization header.
     *
     * @param Request $request
     * @return array|null
     * @throws \Exception
     */
    private static function parseToken(Request $request): ?array
    {
        $token = self::getBearerToken();

        if ($request->httpMethod !== 'OPTIONS') {
            if (!$token) {
                throw new \Exception('Cannot get JWT token.');
            }
            return self::decodeToken($token);
        }

        return null;
    }

    /**
     * Creates a signed JWT for a user.
     *
     * @param User $user
     * @return string
     */
    public static function createToken(User $user): string
    {
        $payload = self::TOKEN_BODY;
        $payload['UserID'] = $user->getID();
        $payload['username'] = $user->getUsername();
        $payload['firstname'] = $user->getFirstname();
        $payload['lastname'] = $user->getLastname();
        $payload['mail'] = $user->getMail();
        $payload['exp'] = time() + Env::get("TOKEN_EXPIRED_IN_S");

        return self::generateToken($payload);
    }

    /**
     * Creates a refresh token.
     *
     * @param User $user
     * @return string
     */
    private static function createRefreshToken(User $user): string
    {
        return self::generateToken([
            'UserID' => $user->getID(),
            'timestamp' => time(),
        ]);
    }

    /**
     * Generates a JWT string from payload.
     *
     * @param array $payload
     * @return string
     */
    private static function generateToken(array $payload): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $body = json_encode($payload);

        Logging::getInstance()->add(
            Logging::LOG_TYPE_AUTHORISATION,
            "Token created for user ID " . ($payload['UserID'] ?? 'unknown')
        );

        $base64UrlHeader = self::base64url_encode($header);
        $base64UrlBody = self::base64url_encode($body);

        $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlBody, self::generateSecret(), true);
        $base64UrlSignature = self::base64url_encode($signature);

        return $base64UrlHeader . '.' . $base64UrlBody . '.' . $base64UrlSignature;
    }

    /**
     * Validates a JWT string.
     *
     * @param string $jwt
     * @return array|null
     */
    public static function decodeToken(string $jwt, $skipValidation = false): ?array
    {
        [$header, $payload, $signatureProvided] = explode('.', $jwt);

        $decodedHeader = base64_decode($header);
        $decodedPayload = base64_decode($payload);

        $expiration = json_decode($decodedPayload)->exp ?? null;
        $tokenExpired = $expiration === null || ($expiration - time()) < 0;

        $base64UrlHeader = self::base64url_encode($decodedHeader);
        $base64UrlPayload = self::base64url_encode($decodedPayload);
        $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, self::generateSecret(), true);
        $base64UrlSignature = self::base64url_encode($signature);

        if ($skipValidation || ($base64UrlSignature === $signatureProvided && (!$tokenExpired || Env::get("TOKEN_EXPIRED_IN_S") === null))) {
            return json_decode($decodedPayload, true);
        }

        return null;
    }

    /**
     * Encodes a string to base64 URL format.
     *
     * @param string $str
     * @return string
     */
    private static function base64url_encode(string $str): string
    {
        return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
    }

    /**
     * Generates a secret key based on server environment.
     *
     * @return string
     */
    private static function generateSecret(): string
    {
        return hash('sha256', "gW7pXv29LmQzR5Tb1AyFsd8eJkUqNv3HpoCxrVtYZ4BWMa1lj9squPfOEgH2KnDX");
    }

    /**
     * Retrieves the Authorization header from the request.
     *
     * @return string|null
     */
    private static function getAuthorizationHeader(): ?string
    {
        return $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? (
            function_exists('apache_request_headers') ?
            (array_change_key_case(apache_request_headers(), CASE_LOWER)['authorization'] ?? null) : null
        );
    }

    /**
     * Extracts Bearer token from Authorization header.
     *
     * @return string|null
     */
    private static function getBearerToken(): ?string
    {
        $header = self::getAuthorizationHeader();
        return (preg_match('/Bearer\s(\S+)/', $header ?? '', $matches)) ? $matches[1] : null;
    }
}

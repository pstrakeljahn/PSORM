<?php

namespace PS\Core\Api;

use PS\Core\Api\Authmethodes\AuthMethodeInterface;
use ReflectionClass;

class Session
{
    private $authInstance;

    public function __construct($login)
    {
        foreach (include 'authmethodes/Methodes.php' as $className) {
            $reflection = new ReflectionClass($className);
            if ($reflection->implementsInterface(AuthMethodeInterface::class)) {
                $this->authInstance = new $className($login);
            }
        }
    }

    public final static function getInstance($login = false): Session
    {
        return new self($login);
    }

    public final function getUser()
    {
        return $this->authInstance->getUser();
    }

    public final function getLoggedIn(): bool
    {
        return $this->authInstance->getLoggedIn();
    }

    public final function login(Request $request): ?array
    {
        if ($request->httpMethod !== 'POST') {
            throw new \Exception('Use POST method to login');
        }
        return $this->authInstance->login($request);
    }
}

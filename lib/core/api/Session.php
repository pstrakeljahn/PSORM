<?php

namespace PS\Core\Api;

use PS\Core\Api\Authmethodes\AuthMethodeInterface;
use ReflectionClass;

class Session
{
    private $authInstance;
    private $isServiceInstance = false;

    public function __construct($login)
    {
        $this->isServiceInstance = defined('SERVICE');
        if ($this->isServiceInstance) {
            $login = true;
        }
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
        if ($this->isServiceInstance) {
            return null;
        }
        return $this->authInstance->getUser();
    }

    public final function getLoggedIn(): bool
    {
        if ($this->isServiceInstance) {
            return true;
        }
        return $this->authInstance->getLoggedIn();
    }

    public final function login(): ?array
    {
        return $this->authInstance->login();
    }

    public final function logout()
    {
        return $this->authInstance->logout();
    }

    public final function refresh()
    {
        return $this->authInstance->refresh();
    }
}

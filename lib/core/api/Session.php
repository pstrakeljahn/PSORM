<?php

namespace PS\Core\Api;

use PS\Core\Api\Authmethodes\AuthMethodeInterface;
use PS\Core\Api\Authmethodes\HeaderAuthentification;
use PS\Core\Helper\Env;
use ReflectionClass;

/**
 * Class Session
 *
 * Handles authentication and session state using configured auth methods.
 */
class Session
{
    /** @var AuthMethodeInterface|null The resolved authentication instance */
    private ?AuthMethodeInterface $authInstance = null;

    /** @var bool Indicates if this session runs in service (non-auth) context */
    private bool $isServiceInstance = false;

    /**
     * Session constructor.
     *
     * Initializes the appropriate auth method, or acts as system service session.
     *
     * @param bool $login Whether login logic should be triggered.
     * @throws \Exception If no valid auth method implementation is found.
     */
    public function __construct(bool $login = false)
    {
        $this->isServiceInstance = defined('SERVICE');

        if ($this->isServiceInstance) {
            $login = true;
        }

        if (Env::get("USE_HEADER_AUTH")) {
            $this->authInstance = new HeaderAuthentification();
        } else {
            foreach (include 'authmethodes/Methodes.php' as $className) {
                $reflection = new ReflectionClass($className);
                if ($reflection->implementsInterface(AuthMethodeInterface::class)) {
                    $this->authInstance = new $className($login);
                    break;
                }
            }

            if (!$this->authInstance) {
                throw new \Exception("No valid authentication method found.");
            }
        }
    }

    /**
     * Factory method for getting a session instance.
     *
     * @param bool $login Whether login should be attempted on initialization.
     * @return self
     */
    public static function getInstance(bool $login = false): self
    {
        return new self($login);
    }

    /**
     * Returns the authenticated user object.
     *
     * @return mixed|null
     */
    public function getUser(): mixed
    {
        if ($this->isServiceInstance) {
            return null;
        }

        return $this->authInstance?->getUser();
    }

    /**
     * Returns whether the session is logged in.
     *
     * @return bool
     */
    public function getLoggedIn(): bool
    {
        if ($this->isServiceInstance) {
            return true;
        }

        return $this->authInstance?->getLoggedIn() ?? false;
    }

    /**
     * Triggers login flow and returns login result.
     *
     * @return array|null
     */
    public function login(): ?array
    {
        return $this->authInstance?->login();
    }

    /**
     * Logs the current user out.
     *
     * @return mixed
     */
    public function logout(): mixed
    {
        return $this->authInstance?->logout();
    }

    /**
     * Refreshes the session (e.g. token renewal).
     *
     * @return mixed
     */
    public function refresh(): mixed
    {
        return $this->authInstance?->refresh();
    }
}

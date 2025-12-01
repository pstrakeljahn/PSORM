<?php

namespace PS\Core\Api\Authmethodes;

use PS\Core\Api\Request;
use Object\User;
use ObjectPeer\UserPeer;
use PS\Core\Database\Criteria;

/**
 * Class HeaderAuthentification
 *
 * Handles authentication via HeaderAuthentification.
 */
class HeaderAuthentification implements AuthMethodeInterface
{
    /** @var Request Current request object */
    private Request $request;

    private ?User $user = null;

    public function __construct()
    {
        $this->request = Request::getInstance();
        $this->validateHeader();
    }

    /**
     * @return User|null
     * @throws \Exception
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @return bool
     */
    public function getLoggedIn(): bool
    {
        return $this->user !== null;
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    public function login(): ?array
    {
        throw new \Exception("HeaderAuth does not require a login");
        return [];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function logout(): array
    {
        throw new \Exception("HeaderAuth does not require a logout");
        return [];
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    public function refresh(): ?array
    {
        return [];
    }

    private function validateHeader()
    {
        $arrHeader = $this->request->getHeader();
        $relevantKeys = ["Username", "Password"];
        $missingKeys = [];
        $authData = [];
        foreach ($relevantKeys as $relevantKey) {
            if (!isset($arrHeader[ucfirst($relevantKey)])) {
                $missingKeys[] = $relevantKey;
            } else {
                $authData[ucfirst($relevantKey)] = $arrHeader[ucfirst($relevantKey)];
            }
        }
        if (count($missingKeys)) {
            throw new \Exception(sprintf("Include following keys into the header: %s", implode(", ", $missingKeys)));
        }

        $this->fetchUser($authData);
    }

    private function fetchUser(array $authData)
    {
        $username = $authData["Username"];

        $allUsersWithUserName = UserPeer::find(
            Criteria::getInstance()
                ->add(UserPeer::USERNAME, $username)
                ->add(UserPeer::ALLOWHEADERAUTH, true)
        );

        foreach ($allUsersWithUserName as $user) {
            if (password_verify($authData['Password'], $user->getPassword())) {
                $this->user = $user;
                return;
            }
        }

        throw new \Exception("Credentials invalid!");
    }
}

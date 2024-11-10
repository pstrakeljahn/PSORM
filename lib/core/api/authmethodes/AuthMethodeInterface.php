<?php

namespace PS\Core\Api\Authmethodes;

use Object\User;

interface AuthMethodeInterface
{
    public function getUser(): ?User;
    public function getLoggedIn(): bool;
    public function login(): ?array;
    public function logout(): ?array;
    public function refresh(): ?array;
}

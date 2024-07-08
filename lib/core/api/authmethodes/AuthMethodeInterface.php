<?php

namespace PS\Core\Api\Authmethodes;

use Object\User;
use PS\Core\Api\Request;

interface AuthMethodeInterface
{
    public function getUser(array $request): ?User;
    public function getLoggedIn(): bool;
    public function login(Request $request): ?array;
}

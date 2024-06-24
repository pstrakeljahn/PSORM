<?php

namespace PS\Core\Api\Authmethodes;

use PS\Core\Api\Request;

interface AuthMethodeInterface
{
    public function getUser();
    public function getLoggedIn(): bool;
    public function login(Request $request): ?array;
}

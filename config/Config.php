<?php

use PS\Core\Api\Authmethodes\BearerToken;

class Config
{
    // Globals
    const BASE_PATH = __DIR__ . '/../';
    const DEBUG = true;
    const ALLOWED_ORIGINS = ['localhost'];

    // Login Method - If not specified autochoosen
    const LOGIN_METHOD = BearerToken::class;
    const TOKEN_EXPIRED_IN_S = 3600;

    // Databaseinformation
    const HOST = 'localhost';
    const PORT = '3306';
    const USERNAME = 'dev';
    const PASSWORD = 'qwer1234';
    const DATABASE = 'test';
    const CHARSET = 'utf8mb4';
}

<?php

class Config
{
    // Globals
    const DEBUG = true;
    const ALLOWED_ORIGINS = ['localhost'];
    
    // Login Method - If not specified autochoosen
    const LOGIN_METHOD = PS\Core\Api\Authmethodes\BearerToken::class;
    const TOKEN_EXPIRED_IN_S = 3600;
    
    // Databaseinformation
    const HOST = 'localhost';
    const PORT = '3306';
    const USERNAME = 'dev';
    const PASSWORD = 'qwer1234';
    const DATABASE = 'test';
    const CHARSET = 'utf8mb4';
    
    // Pathes
    const BASE_PATH = __DIR__ . '/../';
    const TEMP_FOLDER = __DIR__ . '/../temp/';
    const FILES_FOLDER = __DIR__ . '/../files/';

    // Initials data
    const ADMIN_USER = [
        ObjectPeer\UserPeer::USERNAME => 'admin',
        ObjectPeer\UserPeer::PASSWORD => 'D<2;x3obA+<i5'
    ];

}

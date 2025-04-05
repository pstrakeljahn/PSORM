<?php

class Config
{
    // Globals
    const ALLOWED_ORIGINS = ['localhost', 'http://localhost:4200'];

    // Pathes
    const BASE_PATH = __DIR__ . '/../';
    const TEMP_FOLDER = __DIR__ . '/../temp/';
    const FILES_FOLDER = __DIR__ . '/../files/';
    const LOG_FOLDER = __DIR__ . '/../logs/';

    // Initials data
    const ADMIN_USER = [
        ObjectPeer\UserPeer::USERNAME => 'admin',
        ObjectPeer\UserPeer::PASSWORD => 'D<2;x3obA+<i5'
    ];
}

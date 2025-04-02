<?php

namespace PS\Core\Helper;

use Config;
use Dotenv\Dotenv;

class Env
{
    public static final function load(): void
    {
        $dotenv = Dotenv::createImmutable(Config::BASE_PATH);
        $dotenv->load();
    }

    public static final function get(string $key): mixed
    {
        if (isset($_ENV[$key])) {
            switch ($_ENV[$key]) {
                case "true":
                    return true;
                case "false":
                    return false;
                default:
                    return $_ENV[$key];
            }
        }
        return null;
    }
}

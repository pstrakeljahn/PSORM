<?php

namespace PS\Core\Helper;

use Config;
use Dotenv\Dotenv;

/**
 * Class Env
 *
 * Handles loading and accessing environment variables from .env files.
 */
class Env
{
    /**
     * Loads environment variables from the .env file at the base path.
     *
     * @return void
     */
    public static function load(): void
    {
        $dotenv = Dotenv::createImmutable(Config::BASE_PATH);
        $dotenv->load();
    }

    /**
     * Gets a value from the environment, with automatic conversion for booleans.
     *
     * @param string $key The environment variable name.
     * @param mixed|null $default A default value if the key is not set.
     * @return mixed The environment value, or null/default if not set.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!isset($_ENV[$key])) {
            return $default;
        }

        return match (strtolower($_ENV[$key])) {
            'true'  => true,
            'false' => false,
            default => $_ENV[$key]
        };
    }
}

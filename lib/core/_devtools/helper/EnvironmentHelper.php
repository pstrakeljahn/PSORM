<?php

namespace PS\Core\_devtools\Helper;

use Config;
use Dotenv\Dotenv;
use PS\Core\Database\DBConnector;
use PS\Core\Helper\CliOutputHelper;

class EnvironmentHelper
{
    private const ENV_PATH = Config::BASE_PATH . '.env';
    private const REQUIRED_KEYS = [
        "DB_HOST",
        "DB_PORT",
        "DB_NAME",
        "DB_USER",
        "DB_PASS",
        "DB_CHARSET"
    ];

    public static function createEnvFile(): bool
    {
        $exampleFile = Config::BASE_PATH . 'lib/core/_devtools/templates/.env';

        if (!file_exists(self::ENV_PATH)) {
            if (file_exists($exampleFile)) {
                if (copy($exampleFile, self::ENV_PATH)) {
                    CliOutputHelper::output(".env file was created\n\t => FILL IT!");
                    return true;
                }
                return false;
            }
        }
        return true;
    }

    public static function validateEnv(): bool
    {
        $dotenv = Dotenv::createImmutable(Config::BASE_PATH);
        $dotenv->load();
        $missingKeys = [];
        $missingValues = [];

        foreach (self::REQUIRED_KEYS as $key) {
            if (!isset($_ENV[$key])) {
                $missingKeys[] = $key;
                $missingValues[] = $key;
            } elseif ($_ENV[$key] === "") {
                $missingValues[] = $key;
            }
        }
        if (!empty($missingKeys)) {
            file_put_contents(self::ENV_PATH, PHP_EOL . implode(PHP_EOL, array_map(fn($k) => "$k=", $missingKeys)), FILE_APPEND);
        }
        if (!empty($missingValues)) {
            CliOutputHelper::output(".env is invalid: " . implode(", ", $missingValues));
            return false;
        }
        return true;
    }

    public static function checkDbConnectivity(): bool
    {
        try {
            new DBConnector(true);
            return true;
        } catch (\Exception $e) {
            CliOutputHelper::output("Cannot connect to DB!");
            return false;
        }
    }
}

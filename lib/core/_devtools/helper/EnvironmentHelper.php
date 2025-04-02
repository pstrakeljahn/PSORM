<?php

namespace PS\Core\_devtools\Helper;

use Config;
use PS\Core\Database\DBConnector;
use PS\Core\Helper\CliOutputHelper;
use PS\Core\Helper\Env;

class EnvironmentHelper
{
    private const ENV_PATH = Config::BASE_PATH . '.env';
    private const REQUIRED_KEYS = [
        "DEBUG",
        "DB_HOST",
        "DB_PORT",
        "DB_NAME",
        "DB_USER",
        "DB_PASS",
        "DB_CHARSET"
    ];

    private const ADDITIONAL_KEYS = [
        "MAIL_HOST",
        "MAIL_USER",
        "MAIL_PASS",
        "MAIL_PORT",
        "MAIL_FROM_NAME"
    ];

    public static function createEnvFile(): bool
    {
        $exampleFile = Config::BASE_PATH . 'lib/core/_devtools/templates/.env.template';

        if (!file_exists(self::ENV_PATH)) {
            if (file_exists($exampleFile)) {
                if (copy($exampleFile, self::ENV_PATH)) {
                    CliOutputHelper::output(".env file was created\n\t => FILL IT!");
                    return true;
                }
                return false;
            }
        }

        Env::load();
        return true;
    }

    public static function validateEnv($throw = false): bool
    {
        Env::load();
        $missingKeys = [];
        $missingValues = [];

        foreach (self::REQUIRED_KEYS as $key) {
            if (is_null(Env::get($key))) {
                $missingKeys[] = $key;
                $missingValues[] = $key;
            } elseif (Env::get($key) === "") {
                $missingValues[] = $key;
            }
        }
        foreach (self::ADDITIONAL_KEYS as $key) {
            if (is_null(Env::get($key))) {
                $missingKeys[] = $key;
            }
        }
        if (!empty($missingKeys)) {
            file_put_contents(self::ENV_PATH, PHP_EOL . implode(PHP_EOL, array_map(fn($k) => "$k=", $missingKeys)), FILE_APPEND);
        }
        if (!empty($missingValues)) {
            $exception = ".env is invalid: " . implode(", ", $missingValues);
            if ($throw) {
                throw new \Exception($exception);
            }
            CliOutputHelper::output($exception);
            return false;
        }
        return true;
    }

    public static function checkDbConnectivity($throw = false): bool
    {
        try {
            new DBConnector(true);
            return true;
        } catch (\Exception $e) {
            $exception = "Cannot connect to DB!";
            if ($throw) {
                throw new \Exception($exception);
            }
            CliOutputHelper::output($exception);
            return false;
        }
    }
}

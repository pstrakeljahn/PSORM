<?php

namespace PS\Core\_devtools\Helper;

use Config;
use PS\Core\Database\DBConnector;
use PS\Core\Helper\CliOutputHelper;
use PS\Core\Helper\Env;

/**
 * Class EnvironmentHelper
 *
 * Provides utilities to check and initialize environment (.env) configuration.
 */
class EnvironmentHelper
{
    private const ENV_PATH = Config::BASE_PATH . '.env';

    /** @var string[] Required .env keys for the app to work */
    private const REQUIRED_KEYS = [
        'DEBUG',
        'DB_HOST',
        'DB_PORT',
        'DB_NAME',
        'DB_USER',
        'DB_PASS',
        'DB_CHARSET',
    ];

    /** @var string[] Optional but recommended .env keys */
    private const ADDITIONAL_KEYS = [
        'MAIL_HOST',
        'MAIL_USER',
        'MAIL_PASS',
        'MAIL_PORT',
        'MAIL_FROM_NAME',
    ];

    /**
     * Creates a .env file if it doesn't exist, using a template.
     *
     * @return bool True if created or already exists, false on error.
     */
    public static function createEnvFile(): bool
    {
        $templatePath = Config::BASE_PATH . 'lib/core/_devtools/templates/.env.template';

        if (!file_exists(self::ENV_PATH)) {
            if (file_exists($templatePath)) {
                if (copy($templatePath, self::ENV_PATH)) {
                    CliOutputHelper::output(".env file was created\n\t => FILL IT!");
                    return true;
                }
                return false;
            }
        }

        // .env already exists
        Env::load();
        return true;
    }

    /**
     * Validates that all required and optional .env keys exist and are non-empty.
     * Adds missing keys (empty) to the .env file if needed.
     *
     * @param bool $throw If true, throws Exception on error.
     * @return bool True if valid, false otherwise.
     * @throws \Exception If $throw is true and validation fails.
     */
    public static function validateEnv(bool $throw = false): bool
    {
        Env::load();
        $missingKeys = [];
        $missingValues = [];

        // Required keys
        foreach (self::REQUIRED_KEYS as $key) {
            $value = Env::get($key);
            if (is_null($value)) {
                $missingKeys[] = $key;
                $missingValues[] = $key;
            } elseif ($value === '') {
                $missingValues[] = $key;
            }
        }

        // Additional keys (only warn if missing)
        foreach (self::ADDITIONAL_KEYS as $key) {
            if (is_null(Env::get($key))) {
                $missingKeys[] = $key;
            }
        }

        // Append missing keys to file
        if (!empty($missingKeys)) {
            $linesToAdd = PHP_EOL . implode(PHP_EOL, array_map(fn($k) => "$k=", $missingKeys));
            file_put_contents(self::ENV_PATH, $linesToAdd, FILE_APPEND);
        }

        // Output or throw on missing values
        if (!empty($missingValues)) {
            $errorMessage = ".env is invalid: missing values for: " . implode(', ', $missingValues);
            if ($throw) {
                throw new \Exception($errorMessage);
            }
            CliOutputHelper::output($errorMessage);
            return false;
        }

        return true;
    }

    /**
     * Checks if the database connection can be established.
     *
     * @param bool $throw If true, throws Exception on failure.
     * @return bool True if DB connection is successful, false otherwise.
     * @throws \Exception If $throw is true and DB connection fails.
     */
    public static function checkDbConnectivity(bool $throw = false): bool
    {
        try {
            new DBConnector(true); // without DB selection
            return true;
        } catch (\Exception) {
            $errorMessage = "Cannot connect to DB!";
            if ($throw) {
                throw new \Exception($errorMessage);
            }
            CliOutputHelper::output($errorMessage);
            return false;
        }
    }
}

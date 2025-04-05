<?php

namespace PS\Core\Logging;

use Config;
use PS\Core\Helper\CliOutputHelper;

/**
 * Class Logging
 *
 * Lightweight file-based logger with predefined log types and CLI output support.
 */
class Logging
{
    public const LOG_TYPE_API           = 'api';
    public const LOG_TYPE_ERROR         = 'error';
    public const LOG_TYPE_EXTERNAL      = 'external';
    public const LOG_TYPE_BUILD         = 'build';
    public const LOG_TYPE_DB            = 'db';
    public const LOG_TYPE_AUTHORISATION = 'auth';
    public const LOG_TYPE_MAIL          = 'mail';

    /** @var string[] List of known log types for file creation */
    private const ARRAY_LOG_TYPES = [
        self::LOG_TYPE_API,
        self::LOG_TYPE_ERROR,
        self::LOG_TYPE_EXTERNAL,
        self::LOG_TYPE_BUILD,
        self::LOG_TYPE_DB,
        self::LOG_TYPE_AUTHORISATION,
        self::LOG_TYPE_MAIL,
    ];

    /** @var string Base log directory */
    public const LOG_PATH = Config::BASE_PATH . '/logs/';

    /**
     * Logs a message into the given log type file.
     *
     * @param string $type One of the LOG_TYPE_* constants.
     * @param string $message The log message (timestamp is prepended).
     * @param bool $echo If true, also outputs to STDOUT.
     * @return void
     */
    public function add(string $type, string $message, bool $echo = false): void
    {
        $logFile = self::LOG_PATH . $type . '.log';
        $formattedMessage = CliOutputHelper::output($message, $echo);
        file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    }

    /**
     * Creates the log directory and all expected log files if they do not exist.
     *
     * @return bool True on success, false on failure.
     */
    public static function generateFiles(): bool
    {
        try {
            if (!file_exists(self::LOG_PATH)) {
                mkdir(self::LOG_PATH, 0777, true);
            }

            $originalUmask = umask(0); // temporarily allow full perms

            foreach (self::ARRAY_LOG_TYPES as $type) {
                $logFile = self::LOG_PATH . $type . '.log';
                if (!file_exists($logFile)) {
                    $fh = fopen($logFile, 'wb');
                    if ($fh !== false) {
                        fwrite($fh, '');
                        fclose($fh);
                        chmod($logFile, 0777);
                    }
                }
            }

            umask($originalUmask);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Factory method to get a Logging instance.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        return new self();
    }
}

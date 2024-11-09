<?php

namespace PS\Core\Logging;

use Config;

class Logging
{
    // Define log types
    const LOG_TYPE_API = 'api';
    const LOG_TYPE_ERROR = 'error';
    const LOG_TYPE_EXTERNAL = 'external';
    const LOG_TYPE_BUILD = 'build';
    const LOG_TYPE_DB = 'db';
    const LOG_TYPE_AUTHORISATION = 'auth';

    private const ARRAY_LOG_TYPES = [
        self::LOG_TYPE_API,
        self::LOG_TYPE_API,
        self::LOG_TYPE_EXTERNAL,
        self::LOG_TYPE_BUILD,
        self::LOG_TYPE_DB,
        self::LOG_TYPE_AUTHORISATION
    ];

    const LOG_PATH = Config::BASE_PATH . '/logs/';

    /**
     * Add an entry to the log
     *
     * @param string $type Specify the log. Use LOG_TYPE_$type constant
     * @param string $message Log message. Datetime will be added
     * @param bool $echo Prints message
     * @return bool true if sending was successful
     */
    public function add(string $type, string $message, bool $echo = false): void
    {
        $date = date('Y-m-d H:i:s', time());
        $logEntry = '[' . $date . '] : ' . $message . "\r\n";
        file_put_contents(self::LOG_PATH . $type . '.log', $logEntry, FILE_APPEND);
        if ($echo) {
            echo $logEntry;
        }
    }

    /**
     * Has to be executed to create log-files if they not exist
     *
     * @return void
     */
    public static function generateFiles(): bool
    {
        try {
            if (!file_exists(self::LOG_PATH)) {
                mkdir(self::LOG_PATH, 0777, true);
            }

            $currentUmask = umask();
            umask(0);
            foreach (self::ARRAY_LOG_TYPES as $type) {
                $logFile = self::LOG_PATH . $type . '.log';
                if (!file_exists($logFile)) {
                    $fh = fopen($logFile, 'wb');
                    fwrite($fh, '');
                    chmod($logFile, 0777);
                }
            }
            umask($currentUmask);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Get logging instance
     *
     * @return Logging
     */
    public static function getInstance(): self
    {
        return new self;
    }
}

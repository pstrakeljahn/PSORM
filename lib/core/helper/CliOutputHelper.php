<?php

namespace PS\Core\Helper;

use DateTime;

/**
 * Class CliOutputHelper
 *
 * Helper for printing timestamped messages in CLI tools.
 */
class CliOutputHelper
{
    /**
     * Prints a timestamped message to the console (if enabled) and returns the formatted string.
     *
     * @param string $message The message to output.
     * @param bool $showMessage Whether to echo the message to STDOUT.
     * @return string The formatted message with timestamp.
     */
    public static function output(string $message, bool $showMessage = true): string
    {
        $timestamp = (new DateTime())->format('[Y-m-d H:i:s]');
        $formattedMessage = sprintf("%s %s\n", $timestamp, $message);

        if ($showMessage) {
            echo $formattedMessage;
        }

        return $formattedMessage;
    }
}

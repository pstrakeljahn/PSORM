<?php

namespace PS\Core\Helper;

use DateTime;

class CliOutputHelper
{
    public static function output(string $message, bool $showMessage = true): string
    {
        $now = new DateTime();
        $formattedDate = $now->format('[Y-m-d H:i:s]');
        $string = sprintf("%s %s\n", $formattedDate, $message);

        if ($showMessage) {
            echo $string;
        }
        return $string;
    }
}

<?php

namespace PS\Core\Helper;

use DateTime;

class CliOutputHelper
{
    public static function output(string $message): void
    {
        $now = new DateTime();
        $formattedDate = $now->format('[Y-m-d H:i:s]');

        echo sprintf("%s %s\n", $formattedDate, $message);
    }
}

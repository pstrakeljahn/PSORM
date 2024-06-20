<?php

namespace PS\Core\Devtools;

use Config;

class InstallComposerPackages
{
    public static function install(): void
    {
        $ch = curl_init('https://getcomposer.org/installer');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);

        curl_close($ch);
        $filePath = Config::BASE_PATH . 'lib/core/';
        file_put_contents($filePath . 'composer-setup.php', $data);

        // Install packages
        exec("php $filePath/composer-setup.php");
        exec("php composer.phar --working-dir={$filePath} install", $result);
        unlink("$filePath/composer-setup.php");
        unlink("composer.phar");
        echo "\n";
    }
}

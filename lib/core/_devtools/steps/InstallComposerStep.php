<?php

namespace PS\Core\_devtools\Steps;

use PS\Core\_devtools\Abstracts\BuildStep;
use Config;

class InstallComposerStep extends BuildStep
{
    protected function setStepName(): string
    {
        return 'Installing composer packages';
    }

    protected function setDescription(): string
    {
        return 'This step is used to install required composer packages';
    }

    public function run(): bool
    {
        try {
            $ch = curl_init('https://getcomposer.org/installer');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $data = curl_exec($ch);

            curl_close($ch);
            $filePath = Config::BASE_PATH . 'lib/core/';
            file_put_contents($filePath . 'composer-setup.php', $data);

            exec("php $filePath/composer-setup.php");
            exec("php composer.phar --working-dir={$filePath} install > /dev/null 2>&1", $result);
            unlink("$filePath/composer-setup.php");
            unlink('composer.phar');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

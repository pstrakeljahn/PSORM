<?php

namespace PS\Core\_devtools\Steps;

use PS\Core\_devtools\Abstracts\BuildStep;
use Config;

/**
 * Step to download and install Composer dependencies in the core directory.
 */
final class InstallComposerStep extends BuildStep
{
    protected function setStepName(): string
    {
        return 'Installing composer packages';
    }

    protected function setDescription(): string
    {
        return 'Downloads and installs required composer packages.';
    }

    public function run(): bool
    {
        $corePath = Config::BASE_PATH . 'lib/core/';
        $setupFile = $corePath . 'composer-setup.php';
        $composerPhar = 'composer.phar';

        // Download composer-setup.php
        $ch = curl_init('https://getcomposer.org/installer');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);

        if ($data === false) {
            curl_close($ch);
            return false;
        }

        curl_close($ch);
        file_put_contents($setupFile, $data);

        // Install composer.phar
        exec("php {$setupFile}", $outputInstall, $exitCodeInstall);
        if ($exitCodeInstall !== 0) {
            return false;
        }

        // Run composer install
        exec("php composer.phar --working-dir={$corePath} install --no-interaction --quiet", $outputInstallRun, $exitCodeRun);
        if ($exitCodeRun !== 0) {
            return false;
        }

        // Clean up
        if (file_exists($setupFile)) {
            unlink($setupFile);
        }
        if (file_exists($composerPhar)) {
            unlink($composerPhar);
        }

        // Load autoloader
        $autoloadPath = $corePath . 'vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }

        return true;
    }
}

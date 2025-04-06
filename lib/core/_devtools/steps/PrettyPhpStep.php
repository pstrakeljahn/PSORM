<?php

namespace PS\Core\_devtools\Steps;

use PS\Core\_devtools\Abstracts\BuildStep;
use Config;

/**
 * Downloads and executes Pretty PHP to format generated classes.
 */
final class PrettyPhpStep extends BuildStep
{
    protected function setStepName(): string
    {
        return 'Execute Prettier';
    }

    protected function setDescription(): string
    {
        return 'Formats generated classes using pretty-php.';
    }

    public function run(): bool
    {
        $downloadUrl = "https://github.com/lkrms/pretty-php/releases/latest/download/pretty-php.phar";
        $fileName = "pretty-php.phar";
        $binDir = Config::BASE_PATH . 'lib/core/_devtools/bin/';
        $pharPath = $binDir . $fileName;

        // Ensure bin directory exists
        if (!is_dir($binDir)) {
            if (!mkdir($binDir, 0777, true) && !is_dir($binDir)) {
                return false;
            }
        }

        // Download the PHAR
        $fp = fopen($pharPath, 'w+');
        if ($fp === false) {
            return false;
        }

        $ch = curl_init($downloadUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_FILE           => $fp
        ]);
        curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            fclose($fp);
            return false;
        }

        curl_close($ch);
        fclose($fp);

        // Execute the formatter
        $targetDir = Config::BASE_PATH . 'build';
        exec("php " . escapeshellarg($pharPath) . " " . escapeshellarg($targetDir) . "> /dev/null 2>&1", $output, $exitCode);

        if ($exitCode !== 0) {
            return false;
        }

        // Clean up
        if (file_exists($pharPath)) {
            unlink($pharPath);
        }

        return true;
    }
}

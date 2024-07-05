<?php

namespace PS\Core\_devtools\Steps;

use PS\Core\_devtools\Abstracts\BuildStep;
use Config;

class PrettyPhpStep extends BuildStep
{
    protected function setStepName(): string
    {
        return 'Execute Prettier';
    }

    protected function setDescription(): string
    {
        return 'Pretties generated classes';
    }

    public function run(): bool
    {
        $url = "https://github.com/lkrms/pretty-php/releases/latest/download/pretty-php.phar";
        $outputFile = "pretty-php.phar";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $filepath = Config::BASE_PATH . 'lib/core/_devtools/bin/' . $outputFile;

        $fp = fopen($filepath, 'w+');

        if ($fp === false) {
            return false;
        }
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);
        if (curl_errno($ch)) {
            curl_close($ch);
            fclose($fp);
            return false;
        }
        curl_close($ch);
        fclose($fp);
        exec("php ".$filepath." " . Config::BASE_PATH . "build");
        unlink($filepath);
        return true;
    }
}

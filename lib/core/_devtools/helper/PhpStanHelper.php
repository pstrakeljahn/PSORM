<?php

namespace PS\Core\_devtools\Helper;

use Config;
use PS\Core\Helper\CliOutputHelper;

class PhpStanHelper
{
    private string $phpStanBin;
    public string $level = '5';
    public string $path = Config::BASE_PATH;
    public string $autoloadFile = Config::BASE_PATH . 'lib/core/init.php';

    public function __construct()
    {
        $this->phpStanBin = Config::BASE_PATH . 'lib/core/vendor/bin/phpstan';
    }

    public function runAnalysis(): void
    {
        $phpStan = escapeshellcmd(realpath($this->phpStanBin));
        $level = escapeshellarg($this->level);
        $autoload = escapeshellarg(realpath($this->autoloadFile));

        $escapedPaths = array_map(function ($path) {
            return $path ? escapeshellarg($path) : '';
        }, $this->getFilePaths());

        $escapedPaths = array_filter($escapedPaths);
        $pathsString = implode(' ', $escapedPaths);
        $command = "$phpStan analyse --level=$level --autoload-file=$autoload $pathsString";

        CliOutputHelper::output("Running... ");
        $output = passthru($command);
        echo $output;
    }

    private function getFilePaths(): array
    {
        $excludedPaths = [
            realpath(Config::TEMP_FOLDER),
            realpath(Config::LOG_FOLDER),
            realpath(Config::FILES_FOLDER),
            realpath(Config::BASE_PATH . 'lib/'),
        ];

        $rootDirectories = glob(Config::BASE_PATH . "*", GLOB_ONLYDIR);
        $filteredRootDirs = array_filter($rootDirectories, function ($dir) use ($excludedPaths) {
            return !in_array(realpath($dir), $excludedPaths);
        });

        $libDirectories = glob(Config::BASE_PATH . 'lib/*');
        $filteredLibDirs = array_filter($libDirectories, function ($dir) {
            return realpath($dir) !== realpath(Config::BASE_PATH . 'lib/core');
        });

        $coreDirectories = glob(Config::BASE_PATH . 'lib/core/*');
        $filteredCoreDirs = array_filter($coreDirectories, function ($dir) {
            return realpath($dir) !== realpath(Config::BASE_PATH . 'lib/core/vendor');
        });

        $allFilteredPaths = array_merge(
            array_values($filteredRootDirs),
            array_values($filteredLibDirs),
            array_values($filteredCoreDirs)
        );

        return array_map('realpath', $allFilteredPaths);
    }
}

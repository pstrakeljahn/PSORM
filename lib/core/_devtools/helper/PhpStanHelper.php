<?php

namespace PS\Core\_devtools\Helper;

use Config;
use PS\Core\Helper\CliOutputHelper;

/**
 * Class PhpStanHelper
 *
 * Runs static analysis with PHPStan over the project's codebase.
 */
class PhpStanHelper
{
    /** @var string Path to PHPStan binary */
    private string $phpStanBin;

    /** @var int Analysis level (0–9, default: 5) */
    private int $level = 5;

    /** @var string Path to autoload file */
    private string $autoloadFile;

    /**
     * PhpStanHelper constructor.
     *
     * Initializes the path to the PHPStan binary.
     */
    public function __construct()
    {
        $this->phpStanBin = Config::BASE_PATH . 'lib/core/vendor/bin/phpstan';
        $this->autoloadFile = Config::BASE_PATH . 'lib/core/init.php';
    }

    public function setLevel(int $level): self
    {
        if ($level < 0 || $level > 9) {
            throw new \Exception("Analysis level has to be between 0 and 9.");
        }
        $this->level = $level;
        return $this;
    }

    /**
     * Executes PHPStan analysis for all relevant directories.
     *
     * @return void
     */
    public function runAnalysis(): void
    {
        $phpStan    = escapeshellcmd(realpath($this->phpStanBin));
        $level      = escapeshellarg((string)$this->level);
        $autoload   = escapeshellarg(realpath($this->autoloadFile));
        $pathsArray = $this->getFilePaths();

        $escapedPaths = array_map(
            fn(string $path): string => escapeshellarg($path),
            array_filter($pathsArray)
        );

        $pathsString = implode(' ', $escapedPaths);
        $command = "$phpStan analyse --level=$level --autoload-file=$autoload $pathsString";

        CliOutputHelper::output("Running PHPStan Analysis at level $this->level:");
        passthru($command);
    }

    /**
     * Gathers all project paths to be analyzed, excluding vendor/log/temp/etc.
     *
     * @return string[] List of real paths.
     */
    private function getFilePaths(): array
    {
        $excluded = [
            realpath(Config::BASE_PATH . 'ci/'),
            realpath(Config::TEMP_FOLDER),
            realpath(Config::LOG_FOLDER),
            realpath(Config::FILES_FOLDER),
            realpath(Config::BASE_PATH . 'lib/'),
        ];

        // Root-level directories (e.g., /app, /src)
        $rootDirs = glob(Config::BASE_PATH . "*", GLOB_ONLYDIR);
        $filteredRootDirs = array_filter($rootDirs, fn($dir) => !in_array(realpath($dir), $excluded));

        // lib/* except lib/core
        $libDirs = glob(Config::BASE_PATH . 'lib/*');
        $filteredLibDirs = array_filter(
            $libDirs,
            fn($dir) =>
            realpath($dir) !== realpath(Config::BASE_PATH . 'lib/core')
        );

        // lib/core/* except vendor
        $coreDirs = glob(Config::BASE_PATH . 'lib/core/*');
        $filteredCoreDirs = array_filter(
            $coreDirs,
            fn($dir) =>
            realpath($dir) !== realpath(Config::BASE_PATH . 'lib/core/vendor')
        );

        return array_map('realpath', array_merge(
            $filteredRootDirs,
            $filteredLibDirs,
            $filteredCoreDirs
        ));
    }
}

<?php

namespace PS\Core\_devtools\Steps;

use PS\Core\_devtools\Abstracts\BuildStep;
use Config;
use PS\Core\Logging\Logging;

/**
 * Step responsible for creating backend folder and file structure.
 */
final class BackendStructureCreationStep extends BuildStep
{
    /**
     * @return string
     */
    protected function setStepName(): string
    {
        return 'Creating File/Folder structure';
    }

    /**
     * @return string
     */
    protected function setDescription(): string
    {
        return 'Creating necessary files and folders.';
    }

    /**
     * Executes the step: creates directories and log files.
     *
     * @return bool True if successful
     */
    public function run(): bool
    {
        /** @var array<string, array<int, string>|null> $structure */
        $structure = [
            'ci'                                 => null,
            'build'                              => ['customEndpoints', 'peerBasic', 'basic'],
            basename(Config::LOG_FOLDER)         => ['mails'],
            basename(Config::FILES_FOLDER)       => null,
            basename(Config::TEMP_FOLDER)        => null,
        ];

        return self::createFolders($structure) && self::createLogFiles();
    }

    /**
     * Creates the required directory structure recursively.
     *
     * @param array<string, array<int, string>|null> $structure
     * @return bool
     */
    private static function createFolders(array $structure): bool
    {
        $basePath = Config::BASE_PATH;

        foreach ($structure as $folder => $subfolders) {
            $path = $basePath . $folder;

            if (!is_dir($path) && !mkdir($path, 0777, true)) {
                return false;
            }

            if (is_array($subfolders)) {
                foreach ($subfolders as $subfolder) {
                    $subfolderPath = $path . DIRECTORY_SEPARATOR . $subfolder;

                    if (!is_dir($subfolderPath) && !mkdir($subfolderPath, 0777, true)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Creates default log files via Logging helper.
     *
     * @return bool
     */
    private static function createLogFiles(): bool
    {
        return Logging::generateFiles();
    }
}

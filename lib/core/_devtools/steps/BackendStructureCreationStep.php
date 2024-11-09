<?php

namespace PS\Core\_devtools\Steps;

use PS\Core\_devtools\Abstracts\BuildStep;
use Config;
use PS\Core\Logging\Logging;

class BackendStructureCreationStep extends BuildStep
{
    protected function setStepName(): string
    {
        return 'Creating File/Folder structure';
    }

    protected function setDescription(): string
    {
        return 'Creating neccessary files and fodlers.';
    }

    public function run(): bool
    {
        $structure = [
            'build' => ['customEndpoints', 'peerBasic', 'basic'],
            'logs' => null,
            'files' => null,
            'tmp' => null
        ];

        return self::createFolders($structure) && self::createLogFiles();
    }

    private static function createFolders(array $structure)
    {
        $basePath = Config::BASE_PATH;
        foreach ($structure as $folder => $subfolders) {
            $path = $basePath . $folder;
            if (!is_dir($path)) {
                if (!mkdir($path, 0777, true)) {
                    return false;
                }
            }
            if (is_array($subfolders)) {
                foreach ($subfolders as $subfolder) {
                    $subfolderPath = $path . DIRECTORY_SEPARATOR . $subfolder;
                    if (!is_dir($subfolderPath)) {
                        if (!mkdir($subfolderPath, 0777, true)) {
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }

    private static function createLogFiles()
    {
        return Logging::generateFiles();
    }
}

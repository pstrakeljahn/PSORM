<?php

namespace PS\Core\_devtools\Steps;

use PS\Core\_devtools\Abstracts\BuildStep;
use Config;

/**
 * Step to create symbolic links for development tools or resources.
 */
final class CreateSymLinks extends BuildStep
{
    /**
     * Returns the name of the build step.
     *
     * @return string
     */
    protected function setStepName(): string
    {
        return 'Creating Symlinks';
    }

    /**
     * Returns a short description of what the step does.
     *
     * @return string
     */
    protected function setDescription(): string
    {
        return 'Creates symbolic links for developer tooling.';
    }

    /**
     * Executes the symlink creation.
     *
     * @return bool True if successful
     */
    public function run(): bool
    {
        /** @var array<string, string> $arrSymLinks */
        $arrSymLinks = [
            realpath(Config::BASE_PATH . 'lib/core/_devtools/BuildInstance.php') => Config::BASE_PATH . 'ci/devtool_buildInstance.php',
        ];

        foreach ($arrSymLinks as $from => $to) {
            if (!$this->createSymlink($from, $to)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Creates a symbolic link from target to path if valid and necessary.
     *
     * @param string $target The file that the symlink should point to
     * @param string $linkPath The symlink path to be created
     * @return bool True on success
     */
    private function createSymlink(string $target, string $linkPath): bool
    {
        if (!file_exists($target)) {
            return false;
        }

        if (is_link($linkPath)) {
            $currentTarget = readlink($linkPath);
            if ($currentTarget === $target) {
                return true; // Already correct
            }

            if (!unlink($linkPath)) {
                return false;
            }
        } elseif (file_exists($linkPath)) {
            return false; // Path already exists but is not a symlink
        }

        return symlink($target, $linkPath);
    }
}

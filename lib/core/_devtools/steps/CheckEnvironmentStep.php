<?php

namespace PS\Core\_devtools\Steps;

use PS\Core\_devtools\Abstracts\BuildStep;
use PS\Core\_devtools\Helper\EnvironmentHelper;

/**
 * Step that checks and prepares the .env environment and database connectivity.
 */
final class CheckEnvironmentStep extends BuildStep
{
    /**
     * Defines the step name for output/logging.
     *
     * @return string
     */
    protected function setStepName(): string
    {
        return 'Check Environment';
    }

    /**
     * Provides a short description for this build step.
     *
     * @return string
     */
    protected function setDescription(): string
    {
        return 'Validates .env configuration and database connectivity.';
    }

    /**
     * Runs the environment validation process.
     *
     * @return bool True if successful, otherwise an exception is thrown
     * @throws \Exception
     */
    public function run(): bool
    {
        EnvironmentHelper::createEnvFile();
        EnvironmentHelper::validateEnv(true);
        EnvironmentHelper::checkDbConnectivity(true);

        return true;
    }
}

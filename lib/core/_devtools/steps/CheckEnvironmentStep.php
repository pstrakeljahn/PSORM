<?php

namespace PS\Core\_devtools\Steps;

use PS\Core\_devtools\Abstracts\BuildStep;
use Config;
use PS\Core\_devtools\Helper\EnvironmentHelper;

class CheckEnvironmentStep extends BuildStep
{
    protected function setStepName(): string
    {
        return 'Check Environment';
    }

    protected function setDescription(): string
    {
        return '';
    }

    public function run(): bool
    {
        try {
            EnvironmentHelper::createEnvFile();
            EnvironmentHelper::validateEnv(true);
            EnvironmentHelper::checkDbConnectivity(true);
            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}

<?php

namespace PS\Core\Src\Services;

use PS\Core\Service\AbstractService;

class ExampleService extends AbstractService
{
    protected int $sleep = 2;

    protected function handle(): void
    {
        echo "ExampleService is runnung...\n";
    }
}

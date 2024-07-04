<?php

namespace PS\Core\_devtools;

use PS\Core\_devtools\Abstracts\BuildStep;
use PS\Core\_devtools\Steps\BackendStructureCreationStep;
use PS\Core\_devtools\Steps\BuildBasicClasses;
use PS\Core\_devtools\Steps\InstallComposerStep;

class BuildInstance
{
    public static function steps(): array
    {
        return [
            BackendStructureCreationStep::class,
            // InstallComposerStep::class,
            BuildBasicClasses::class
        ];
    }

    public final static function run()
    {
        BuildStep::workThroughSteps(self::steps());
    }
}

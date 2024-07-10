<?php

namespace PS\Core\_devtools;

use PS\Core\_devtools\Abstracts\BuildStep;
use PS\Core\_devtools\Steps\BackendStructureCreationStep;
use PS\Core\_devtools\Steps\BuildBasicClasses;
use PS\Core\_devtools\Steps\InsertInitialData;
use PS\Core\_devtools\Steps\InstallComposerStep;
use PS\Core\_devtools\Steps\PrepareDatabase;
use PS\Core\_devtools\Steps\PrettyPhpStep;

class BuildInstance
{
    public static function steps(): array
    {
        return [
            BackendStructureCreationStep::class,
            InstallComposerStep::class,
            BuildBasicClasses::class,
            PrettyPhpStep::class,
            PrepareDatabase::class,
            InsertInitialData::class
        ];
    }

    public final static function run()
    {
        BuildStep::workThroughSteps(self::steps());
    }
}

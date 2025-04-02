<?php

namespace PS\Core\_devtools;

use PS\Core\_devtools\Abstracts\BuildStep;
use PS\Core\_devtools\Helper\EnvironmentHelper;
use PS\Core\_devtools\Steps\BackendStructureCreationStep;
use PS\Core\_devtools\Steps\BuildBasicClasses;
use PS\Core\_devtools\Steps\CheckEnvironmentStep;
use PS\Core\_devtools\Steps\GetEndpoints;
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
            GetEndpoints::class,
            InstallComposerStep::class,
            CheckEnvironmentStep::class,
            BuildBasicClasses::class,
            PrettyPhpStep::class,
            PrepareDatabase::class,
            InsertInitialData::class
        ];
    }

    public final static function run()
    {
        self::printPreambel();
        BuildStep::workThroughSteps(self::steps());
    }

    private static function printPreambel(): void
    {
        $text = 'Building Instance';
        $borderLength = 64;
        $borderSymbol = '*';
        $textLength = strlen($text);

        $text = substr($text, 0, $borderLength - 4);
        $textLength = strlen($text);

        $padding = ($borderLength - 2 - $textLength) / 2;
        $leftPadding = intval(floor($padding));
        $rightPadding = intval(ceil($padding));

        $border = str_repeat($borderSymbol, $borderLength);
        $textLine = $borderSymbol . str_repeat(' ', $leftPadding) . $text . str_repeat(' ', $rightPadding) . $borderSymbol;

        echo $border . PHP_EOL;
        echo $textLine . PHP_EOL;
        echo $border . PHP_EOL;
    }
}

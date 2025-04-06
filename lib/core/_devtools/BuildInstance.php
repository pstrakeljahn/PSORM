<?php

namespace PS\Core\_devtools;

use PS\Core\_devtools\Abstracts\BuildStep;
use PS\Core\_devtools\Steps\BackendStructureCreationStep;
use PS\Core\_devtools\Steps\BuildBasicClasses;
use PS\Core\_devtools\Steps\CheckEnvironmentStep;
use PS\Core\_devtools\Steps\CreateSymLinks;
use PS\Core\_devtools\Steps\GetEndpoints;
use PS\Core\_devtools\Steps\InsertInitialData;
use PS\Core\_devtools\Steps\InstallComposerStep;
use PS\Core\_devtools\Steps\PrepareDatabase;
use PS\Core\_devtools\Steps\PrettyPhpStep;

final class BuildInstance
{
    /**
     * Returns the ordered list of build steps to execute.
     *
     * @return array<class-string>
     */
    public static function steps(): array
    {
        return [
            BackendStructureCreationStep::class,
            GetEndpoints::class,
            InstallComposerStep::class,
            CheckEnvironmentStep::class,
            BuildBasicClasses::class,
            PrettyPhpStep::class,
            CreateSymLinks::class,
            PrepareDatabase::class,
            InsertInitialData::class
        ];
    }

    /**
     * Executes the build process.
     *
     * @return void
     */
    public static function run(): void
    {
        define('SERVICE', 1);
        self::printPreamble();
        BuildStep::workThroughSteps(self::steps());
    }

    /**
     * Outputs a formatted build preamble to the console.
     *
     * @return void
     */
    private static function printPreamble(): void
    {
        $text = 'Building Instance';
        $borderLength = 64;
        $borderSymbol = '*';
        $text = substr($text, 0, $borderLength - 4);

        $textLength = strlen($text);
        $padding = ($borderLength - 2 - $textLength) / 2;
        $leftPadding = (int)floor($padding);
        $rightPadding = (int)ceil($padding);

        $border = str_repeat($borderSymbol, $borderLength);
        $textLine = $borderSymbol
            . str_repeat(' ', $leftPadding)
            . $text
            . str_repeat(' ', $rightPadding)
            . $borderSymbol;

        echo $border . PHP_EOL;
        echo $textLine . PHP_EOL;
        echo $border . PHP_EOL;
    }
}

// Entry point
require_once '../lib/core/init.php';

(new BuildInstance())->run();

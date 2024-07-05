<?php

namespace PS\Core\_devtools\Abstracts;

use PS\Core\Helper\CliOutputHelper;

abstract class BuildStep
{
    abstract protected function setStepName(): string;
    abstract protected function setDescription(): string;
    abstract public function run(): bool;

    public final function execute(): void
    {
        CliOutputHelper::output($this->setStepName());
        $this->run();
    }

    public final static function workThroughSteps(array $stepClasses)
    {
        self::printPreambel();
        foreach ($stepClasses as $class) {
            if (is_subclass_of($class, BuildStep::class)) {
                $instance = new $class;
                $instance->execute();
            }
        }
    }

    private static function printPreambel(): void
    {
        $text = 'Building Instance';
        $borderLength = 64;
        $borderSymbol = '*';
        $textLength = strlen($text);

        if ($textLength > ($borderLength - 4)) {
            $text = substr($text, 0, $borderLength - 4);
            $textLength = strlen($text);
        }

        $padding = ($borderLength - 2 - $textLength) / 2;
        $leftPadding = floor($padding);
        $rightPadding = ceil($padding);

        $border = str_repeat($borderSymbol, $borderLength);
        $textLine = $borderSymbol . str_repeat(' ', $leftPadding) . $text . str_repeat(' ', $rightPadding) . $borderSymbol;

        echo $border . PHP_EOL;
        echo $textLine . PHP_EOL;
        echo $border . PHP_EOL;
    }
}

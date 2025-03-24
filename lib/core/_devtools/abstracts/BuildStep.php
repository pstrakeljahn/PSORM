<?php

namespace PS\Core\_devtools\Abstracts;

use PS\Core\Helper\CliOutputHelper;
use PS\Core\Logging\Logging;

abstract class BuildStep
{
    abstract protected function setStepName(): string;
    abstract protected function setDescription(): string;
    abstract public function run(): bool;

    public final function execute(): void
    {
        $log = Logging::getInstance();
        try {
            CliOutputHelper::output($this->setStepName());
            $this->run();
            $log->add(Logging::LOG_TYPE_BUILD, "✅ " . $this->setStepName());
        } catch (\Exception $e) {
            $log->add(Logging::LOG_TYPE_BUILD, "❌ " . $this->setStepName() . ": " . $e->getMessage());
        }
    }

    public final static function workThroughSteps(array $stepClasses)
    {
        foreach ($stepClasses as $class) {
            if (is_subclass_of($class, BuildStep::class)) {
                $instance = new $class;
                $instance->execute();
            }
        }
    }
}

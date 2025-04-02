<?php

namespace PS\Core\_devtools\Abstracts;

use PS\Core\Helper\CliOutputHelper;
use PS\Core\Logging\Logging;

abstract class BuildStep
{
    abstract protected function setStepName(): string;
    abstract protected function setDescription(): string;
    abstract public function run(): bool;

    public final function execute(): bool
    {
        $log = Logging::getInstance();
        $success = false;
        try {
            CliOutputHelper::output($this->setStepName());
            $success = $this->run();
            $log->add(Logging::LOG_TYPE_BUILD, "✅ " . $this->setStepName());
        } catch (\Exception $e) {
            $log->add(Logging::LOG_TYPE_BUILD, "❌ " . $this->setStepName() . ": " . $e->getMessage());
            throw $e;
        }
        return $success;
    }

    public final static function workThroughSteps(array $stepClasses)
    {
        foreach ($stepClasses as $class) {
            try {
                if (is_subclass_of($class, BuildStep::class)) {
                    $instance = new $class;
                    $instance->execute();
                }
            } catch (\Exception $e) {
                CliOutputHelper::output("\t -> Error occured: " . $e->getMessage());
                break;
            }
        }
    }
}

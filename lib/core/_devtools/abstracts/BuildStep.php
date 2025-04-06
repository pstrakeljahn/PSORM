<?php

namespace PS\Core\_devtools\Abstracts;

use PS\Core\Helper\CliOutputHelper;
use PS\Core\Logging\Logging;

/**
 * Abstract class for all build steps.
 */
abstract class BuildStep
{
    /**
     * Returns the name of the build step.
     */
    abstract protected function setStepName(): string;

    /**
     * Returns a description of what the step does.
     */
    abstract protected function setDescription(): string;

    /**
     * Executes the actual logic of the step.
     *
     * @return bool True if step was successful, false otherwise.
     */
    abstract public function run(): bool;

    /**
     * Executes this step with logging and CLI output.
     *
     * @return bool
     */
    public final function execute(): bool
    {
        $stepName = $this->setStepName();
        $log = Logging::getInstance();

        try {
            $success = $this->run();
            if ($success) {
                CliOutputHelper::output("✅  {$stepName}");
                $log->add(Logging::LOG_TYPE_BUILD, "✅ '{$stepName}'");
            } else {
                CliOutputHelper::output("❌  {$stepName}");
                $log->add(Logging::LOG_TYPE_BUILD, "❌ '{$stepName}' failed without exception");
            }

            return $success;
        } catch (\Throwable $e) {
            $message = "❌ '{$stepName}' crashed:\n\t" . $e->getMessage();
            CliOutputHelper::output($message);
            $log->add(Logging::LOG_TYPE_BUILD, $message);
            return false;
        }
    }

    /**
     * Executes a list of build step classes.
     *
     * @param array $stepClasses
     * @return void
     */
    public final static function workThroughSteps(array $stepClasses): void
    {
        foreach ($stepClasses as $className) {
            if (!is_subclass_of($className, BuildStep::class)) {
                CliOutputHelper::output("⚠️  Skipping invalid step: '{$className}'");
                continue;
            }

            try {
                /** @var BuildStep $instance */
                $instance = new $className();
                $instance->execute();
            } catch (\Throwable $e) {
                CliOutputHelper::output("💥  Step failed: " . $e->getMessage());
            }
        }
    }
}

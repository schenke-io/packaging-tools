<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;

/**
 * Task definition for grouping other tasks.
 *
 * This class allows the definition of named groups of commands in the
 * configuration. This is useful for creating shortcuts for multiple
 * development tasks that should be run together.
 *
 * Functional details:
 * - Constructor: Accepts a list of task names to be grouped
 * - schema(): Validates that the configuration contains the allowed tasks in the group
 * - explainConfig(): Lists the scripts included in the group
 * - packages(): Groups typically don't have their own package requirements
 * - commands(): Resolves each task in the group into a Composer script reference (prefixed with @)
 * - explainTask(): Shows which scripts will be run in the task
 */
class GroupDefinition extends BaseDefinition
{
    /**
     * @param  array<int, string>  $tasks
     */
    public function __construct(protected array $tasks)
    {
        parent::__construct();
    }

    /**
     * @return array<int, string>
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    /**
     * return the schema of the configuration for this SetupDefinitionInterface
     */
    public function schema(): Schema
    {
        return Expect::anyOf(false, Expect::arrayOf(Expect::anyOf(...$this->tasks)))->default($this->tasks);
    }

    /**
     * return help text for this config key
     */
    public function explainConfig(): string
    {
        return 'false = disabled, or an array of scripts to include in this group: '.implode(', ', $this->tasks);
    }

    /**
     * line or lines which will be executed when the script is called
     */
    protected function getCommands(Config $config): string|array
    {
        $return = [];
        /**
         * iterate over tasks in the group and check if they are enabled in config
         */
        foreach ($this->tasks as $task) {
            if (in_array($task, array_keys((array) $config->config))) {
                if ($config->config->$task) {
                    /**
                     * prefixing with @ tells composer to run another script
                     */
                    $return[] = '@'.$task;
                }
            }
        }

        return $return;
    }

    /**
     * return help text for task
     */
    public function explainTask(): string
    {
        return 'run all scripts: '.implode(', ', $this->tasks);
    }
}

<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;

/**
 * Class GroupDefinition
 *
 * Task definition for grouping other tasks.
 *
 * Main Responsibilities:
 * - Task Bundling: Allows the definition of named groups of commands in the configuration.
 * - Dynamic Schema: Validates that the configuration contains allowed tasks in the group.
 * - Composer Integration: Resolves group tasks into Composer script references (prefixed with @).
 * - Configuration Explanation: Lists the scripts included in the group for user feedback.
 *
 * Usage Example:
 * ```php
 * $group = new GroupDefinition(['analyse', 'test', 'pint']);
 * $schema = $group->schema();
 * ```
 */
class GroupDefinition extends BaseDefinition
{
    /**
     * @param  array<int, string>  $tasks  List of task names to be grouped.
     */
    public function __construct(protected array $tasks)
    {
        parent::__construct();
    }

    /**
     * Return the list of tasks in the group.
     *
     * @return array<int, string>
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    /**
     * Return the schema of the configuration for this SetupDefinitionInterface.
     */
    public function schema(): Schema
    {
        return Expect::anyOf(
            Expect::null(),
            Expect::bool(),
            Expect::arrayOf(Expect::anyOf(...$this->tasks))
        )->default(null);
    }

    /**
     * Return help text for this config key.
     */
    public function explainConfig(): string
    {
        return 'an array of scripts to include in this group: '.implode(', ', $this->tasks);
    }

    /**
     * Line or lines which will be executed when the script is called.
     */
    protected function getCommands(Config $config): string|array
    {
        $taskName = $this->taskName;
        $val = $config->config->$taskName ?? null;
        $groupTasks = is_array($val) ? $val : $this->tasks;

        $return = [];
        /*
         * Iterate over tasks in the group and check if they are enabled in config.
         */
        foreach ($groupTasks as $task) {
            if (in_array($task, array_keys((array) $config->config))) {
                if ($config->config->$task) {
                    /*
                     * Prefixing with @ tells composer to run another script.
                     */
                    $return[] = '@'.$task;
                }
            }
        }

        return $return;
    }

    /**
     * Return help text for task.
     */
    public function explainTask(): string
    {
        return 'run all scripts: '.implode(', ', $this->tasks);
    }
}

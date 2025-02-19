<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Definition;
use SchenkeIo\PackagingTools\Setup\Requirements;

class GroupDefinition implements Definition
{
    public function __construct(protected array $tasks) {}

    /**
     * return the schema of the configuration for this Definition
     */
    public function schema(): Schema
    {
        return Expect::arrayOf(Expect::anyOf(...$this->tasks))->default($this->tasks);
    }

    /**
     * return help text for this config key
     */
    public function explainConfig(): string
    {
        return 'group of scripts: '.implode(', ', $this->tasks);
    }

    /**
     * return the list of required packages
     */
    public function packages(Config $config): Requirements
    {
        return new Requirements;
    }

    /**
     * line or lines which will be executed when the script is called
     */
    public function commands(Config $config): string|array
    {
        $return = [];
        foreach ($this->tasks as $task) {
            if (in_array($task, array_keys((array) $config->config))) {
                if ($config->config->$task) {
                    $return[] = '@'.$task;
                }
            }
        }

        return $return;
    }

    /**
     * return help text for dev menu
     */
    public function explainUse(): string
    {
        return 'run all scripts: '.implode(', ', $this->tasks);
    }
}

<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use SchenkeIo\PackagingTools\Contracts\SetupDefinitionInterface;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Requirements;

/**
 * Class BaseDefinition
 *
 * Base class for all task definitions to reduce boilerplate.
 *
 * Main Responsibilities:
 * - Naming Convention: Derives the task name from the class name (e.g., PintDefinition -> pint).
 * - Activation Logic: Determines if a task is enabled based on project configuration.
 * - Contract Implementation: Provides the skeletal implementation for SetupDefinitionInterface.
 * - Extension Point: Defines protected methods for subclasses to specify packages and commands.
 *
 * Usage Example:
 * ```php
 * class MyTaskDefinition extends BaseDefinition {
 *     protected function getCommands(Config $config): string|array {
 *         return 'do-something';
 *     }
 * }
 * ```
 */
abstract class BaseDefinition implements SetupDefinitionInterface
{
    protected string $taskName;

    /**
     * Initialize the task definition and resolve its name.
     *
     * @param  string|null  $taskName  Optional explicit task name.
     */
    public function __construct(?string $taskName = null)
    {
        if ($taskName === null) {
            $class = static::class;
            $parts = explode('\\', $class);
            $className = end($parts);
            $baseName = str_replace('Definition', '', $className);
            $this->taskName = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $baseName));
        } else {
            $this->taskName = $taskName;
        }
    }

    /**
     * Explicitly set the task name.
     */
    public function setTaskName(string $taskName): void
    {
        $this->taskName = $taskName;
    }

    /**
     * Return required packages if the task is enabled.
     */
    public function packages(Config $config): Requirements
    {
        if ($this->isEnabled($config)) {
            return $this->getPackages($config);
        }

        return new Requirements;
    }

    /**
     * Return execution commands if the task is enabled.
     */
    public function commands(Config $config): string|array
    {
        if ($this->isEnabled($config)) {
            return $this->getCommands($config);
        }

        return [];
    }

    /**
     * Check if the task is enabled in the configuration.
     */
    protected function isEnabled(Config $config): bool
    {
        $taskName = $this->taskName;
        if ($taskName === 'group') {
            return true;
        }

        $val = $config->config->$taskName ?? null;

        return ! is_null($val) && $val !== false;
    }

    /**
     * Internal method for subclasses to define their required packages.
     */
    protected function getPackages(Config $config): Requirements
    {
        return new Requirements;
    }

    /**
     * Internal method for subclasses to define their execution commands.
     *
     * @return string|array<int,string>
     */
    protected function getCommands(Config $config): string|array
    {
        return [];
    }
}

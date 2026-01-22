<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use SchenkeIo\PackagingTools\Contracts\SetupDefinitionInterface;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Requirements;

/**
 * Base class for all task definitions to reduce boilerplate.
 *
 * This abstract class provides a common foundation for defining setup tasks.
 * It handles the task naming convention (derived from the class name),
 * manages whether a task is enabled based on the project configuration,
 * and provides default implementations for package requirements and commands.
 *
 * Usage:
 * Inherit from this class and implement getPackages() and/or getCommands()
 * to define the specific requirements and actions for a new setup task.
 */
abstract class BaseDefinition implements SetupDefinitionInterface
{
    protected string $taskName;

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

    public function setTaskName(string $taskName): void
    {
        $this->taskName = $taskName;
    }

    public function packages(Config $config): Requirements
    {
        if ($this->isEnabled($config)) {
            return $this->getPackages($config);
        }

        return new Requirements;
    }

    public function commands(Config $config): string|array
    {
        if ($this->isEnabled($config)) {
            return $this->getCommands($config);
        }

        return [];
    }

    protected function isEnabled(Config $config): bool
    {
        $taskName = $this->taskName;
        if ($taskName === 'group') {
            return true;
        }

        $val = $config->config->$taskName ?? null;

        return ! is_null($val) && $val !== false;
    }

    protected function getPackages(Config $config): Requirements
    {
        return new Requirements;
    }

    /**
     * @return string|array<int,string>
     */
    protected function getCommands(Config $config): string|array
    {
        return [];
    }
}

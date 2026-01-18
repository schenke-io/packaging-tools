<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use SchenkeIo\PackagingTools\Contracts\SetupDefinitionInterface;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Requirements;

/**
 * Base class for all task definitions to reduce boilerplate.
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
            $this->taskName = strtolower(str_replace('Definition', '', $className));
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
        if ($this->taskName === 'group') {
            return true;
        }

        return (bool) ($config->config->{$this->taskName} ?? false);
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

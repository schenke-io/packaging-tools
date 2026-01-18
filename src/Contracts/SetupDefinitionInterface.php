<?php

namespace SchenkeIo\PackagingTools\Contracts;

use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Requirements;

/**
 * Interface for all setup tasks.
 *
 * Each task must define its configuration schema, help text for the config
 * and task, required packages, and the commands to be executed
 * when the task is run. This interface ensures a consistent structure
 * for all automated setup and maintenance operations.
 *
 * Methods:
 * - schema(): Returns the Nette schema for task-specific configuration.
 * - explainConfig(): Provides descriptive text for the configuration key.
 * - explainTask(): Provides descriptive text for using the task via console.
 * - packages(): Identifies composer dependencies required by the task.
 * - commands(): Returns the execution strings for the task's operations.
 */
interface SetupDefinitionInterface
{
    /**
     * return the schema of the configuration for this SetupDefinitionInterface
     */
    public function schema(): Schema;

    /**
     * return help text for this config key
     */
    public function explainConfig(): string;

    /**
     * return help text for this task
     */
    public function explainTask(): string;

    /**
     * return the list of required packages
     */
    public function packages(Config $config): Requirements;

    /**
     * line or lines, which will be executed when the script is called
     *
     *
     * @return string|array<int,string>
     */
    public function commands(Config $config): string|array;
}

<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Requirements;

/**
 * Task definition for Laravel Pint code styling.
 *
 * This class implements the SetupDefinitionInterface to provide configuration schema,
 * package requirements, and execution commands for Laravel Pint. It allows
 * the packaging tool to automate code formatting setup and execution.
 *
 * Methods:
 * - schema(): Returns the boolean schema for enabling Pint.
 * - explainConfig(): Explains how to enable Pint in the config.
 * - packages(): Returns 'laravel/pint' dev-requirement if enabled.
 * - commands(): Returns the command to run Pint from the vendor bin.
 * - explainTask(): Provides help text for the Pint command.
 */
class PintDefinition extends BaseDefinition
{
    /**
     * return the schema of the configuration for this SetupDefinitionInterface
     */
    public function schema(): Schema
    {
        return Expect::bool(false);
    }

    /**
     * return help text for this config key
     */
    public function explainConfig(): string
    {
        return 'false = disabled, true = enabled (uses laravel/pint)';
    }

    /**
     * return the list of required packages
     */
    protected function getPackages(Config $config): Requirements
    {
        return Requirements::dev('laravel/pint');
    }

    /**
     * line or lines which will be executed when the script is called
     */
    protected function getCommands(Config $config): string|array
    {
        /**
         * returns the pint command
         */
        return 'vendor/bin/pint';
    }

    /**
     * return help text for task
     */
    public function explainTask(): string
    {
        return 'format the source code';
    }
}

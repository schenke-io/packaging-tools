<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Requirements;

/**
 * Class PintDefinition
 *
 * Task definition for Laravel Pint code styling.
 *
 * Main Responsibilities:
 * - Schema Definition: Defines the configuration schema for enabling/disabling Pint.
 * - Dependency Management: Specifies 'laravel/pint' as a required dev-package.
 * - Task Execution: Provides the command to run Pint for code formatting.
 *
 * Usage Example:
 * ```php
 * $pint = new PintDefinition();
 * $schema = $pint->schema();
 * ```
 */
class PintDefinition extends BaseDefinition
{
    /**
     * Return the schema of the configuration for this SetupDefinitionInterface.
     */
    public function schema(): Schema
    {
        return Expect::bool(false);
    }

    /**
     * Return help text for this config key.
     */
    public function explainConfig(): string
    {
        return 'false = disabled, true = enabled (uses laravel/pint)';
    }

    /**
     * Return the list of required packages.
     */
    protected function getPackages(Config $config): Requirements
    {
        return Requirements::dev('laravel/pint');
    }

    /**
     * Line or lines which will be executed when the script is called.
     */
    protected function getCommands(Config $config): string|array
    {
        return 'vendor/bin/pint';
    }

    /**
     * Return help text for task.
     */
    public function explainTask(): string
    {
        return 'format the source code';
    }
}

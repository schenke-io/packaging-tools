<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Requirements;

/**
 * Class InfectionDefinition
 *
 * Task definition for Infection mutation testing.
 *
 * Main Responsibilities:
 * - Schema Definition: Defines the configuration for enabling Infection mutation testing.
 * - Dependency Management: Specifies 'infection/infection' as a required dev-package.
 * - Task Execution: Provides the command to run mutation tests via vendor bin.
 * - Test Quality: Helps measure the effectiveness of the project's test suite.
 *
 * Usage Example:
 * ```php
 * $infection = new InfectionDefinition();
 * $command = $infection->commands($config);
 * ```
 */
class InfectionDefinition extends BaseDefinition
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
        return 'false = disabled, true = enabled (requires infection/infection)';
    }

    /**
     * Return help text for task.
     */
    public function explainTask(): string
    {
        return 'run mutation tests with Infection';
    }

    /**
     * Return the list of required packages.
     */
    protected function getPackages(Config $config): Requirements
    {
        return Requirements::dev('infection/infection');
    }

    /**
     * Line or lines which will be executed when the script is called.
     */
    protected function getCommands(Config $config): string|array
    {
        return './vendor/bin/infection';
    }
}

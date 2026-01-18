<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Requirements;

/**
 * Task definition for Infection mutation testing.
 *
 * This class manages the configuration and requirements for Infection. It
 * handles the 'infection/infection' package requirement and provides the
 * command to run mutation tests, helping to measure test effectiveness.
 *
 * Implements SetupDefinitionInterface with the following:
 * - schema(): Expects a boolean value to enable/disable mutation testing
 * - explainConfig(): Describes the configuration key purpose
 * - packages(): Returns 'infection/infection' as a dev requirement if enabled
 * - commands(): Provides the CLI command for running Infection
 * - explainTask(): Provides the text shown in the task
 */
class InfectionDefinition extends BaseDefinition
{
    public function schema(): Schema
    {
        return Expect::bool(false);
    }

    public function explainConfig(): string
    {
        return 'false = disabled, true = enabled (requires infection/infection)';
    }

    public function explainTask(): string
    {
        return 'run mutation tests with Infection';
    }

    /**
     * return the list of required packages
     */
    protected function getPackages(Config $config): Requirements
    {
        return Requirements::dev('infection/infection');
    }

    /**
     * line or lines which will be executed when the script is called
     */
    protected function getCommands(Config $config): string|array
    {
        /**
         * return the infection command
         */
        return './vendor/bin/infection';
    }
}

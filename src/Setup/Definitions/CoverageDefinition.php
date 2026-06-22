<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Requirements;

/**
 * Class CoverageDefinition
 *
 * Task definition for code coverage reporting.
 *
 * Main Responsibilities:
 * - Schema Definition: Defines the configuration for enabling code coverage reports.
 * - Test Runner Integration: Appends coverage flags to the selected test runner (Pest or PHPUnit).
 * - Command Generation: Provides the CLI command for executing tests with coverage data generation.
 *
 * Usage Example:
 * ```php
 * $coverage = new CoverageDefinition();
 * $commands = $coverage->commands($config);
 * ```
 */
class CoverageDefinition extends BaseDefinition
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
        return 'false = disabled, true = enabled (adds --coverage to the test runner)';
    }

    /**
     * Return the list of required packages.
     */
    protected function getPackages(Config $config): Requirements
    {
        return Requirements::dev('schenke-io/test-output-formatter');
    }

    /**
     * Line or lines which will be executed when the script is called.
     */
    protected function getCommands(Config $config): string|array
    {
        /*
         * Return coverage command based on test runner.
         */
        return match ($config->config->test) {
            default => [],
            'pest' => 'vendor/bin/pest --coverage',
            'phpunit' => 'vendor/bin/phpunit --coverage'
        };
    }

    /**
     * Return help text for task.
     */
    public function explainTask(): string
    {
        return 'run test with coverage';
    }
}

<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Requirements;

/**
 * Task definition for code coverage reporting.
 *
 * This class handles the configuration for generating code coverage reports.
 * It manages the requirements for clover coverage drivers and provides the
 * command to generate coverage data during test execution.
 *
 * Implements SetupDefinitionInterface with the following:
 * - schema(): Expects a boolean value to enable/disable coverage
 * - explainConfig(): Describes the configuration key purpose
 * - packages(): Returns dependencies based on the selected test runner (pest or phpunit)
 * - commands(): Provides the CLI command with coverage flags
 * - explainTask(): Provides the text shown in the task
 */
class CoverageDefinition extends BaseDefinition
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
        return 'false = disabled, true = enabled (adds --coverage to the test runner)';
    }

    /**
     * return the list of required packages
     */
    protected function getPackages(Config $config): Requirements
    {
        return new Requirements;
    }

    /**
     * line or lines which will be executed when the script is called
     */
    protected function getCommands(Config $config): string|array
    {
        /**
         * return coverage command based on test runner
         */
        return match ($config->config->test) {
            default => [],
            'pest' => 'vendor/bin/pest --coverage',
            'phpunit' => 'vendor/bin/phpunit --coverage'
        };
    }

    /**
     * return help text for task
     */
    public function explainTask(): string
    {
        return 'run test with coverage';
    }
}

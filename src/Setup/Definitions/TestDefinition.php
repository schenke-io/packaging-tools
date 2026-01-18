<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Requirements;

/**
 * Task definition for PHPUnit/Pest testing.
 *
 * This class handles the configuration for running project tests. It ensures
 * that the necessary testing framework is available and provides the
 * command to execute tests with the appropriate options.
 *
 * Methods:
 * - schema(): Defines the schema for test runners (pest, phpunit, or false).
 * - explainConfig(): Explains how to configure the test runner.
 * - packages(): Returns required packages based on the chosen test runner.
 * - commands(): Returns the execution command for the selected test runner.
 * - explainTask(): Provides help text for the test command.
 */
class TestDefinition extends BaseDefinition
{
    /**
     * return the schema of the configuration for this SetupDefinitionInterface
     */
    public function schema(): Schema
    {
        return Expect::anyOf('pest', 'phpunit', '')->default('pest');
    }

    /**
     * return help text for this config key
     */
    public function explainConfig(): string
    {
        return "'' = disabled, 'pest' or 'phpunit' = enabled";
    }

    /**
     * return the list of required packages
     */
    protected function getPackages(Config $config): Requirements
    {
        /**
         * check config for test settings
         */
        return match ($config->config->test) {
            'pest' => Requirements::dev('pestphp/pest'),
            'phpunit' => Requirements::dev('phpunit/phpunit'),
            default => new Requirements,
        };
    }

    /**
     * line or lines which will be executed when the script is called
     */
    protected function getCommands(Config $config): string|array
    {
        /**
         * check config for test settings and return appropriate command
         */
        return match ($config->config->test) {
            'pest' => 'vendor/bin/pest',
            'phpunit' => 'vendor/bin/phpunit',
            default => [],
        };
    }

    /**
     * return help text for task
     */
    public function explainTask(): string
    {
        return 'run the test suite';
    }
}

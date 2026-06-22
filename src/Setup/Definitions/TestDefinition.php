<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Requirements;

/**
 * Class TestDefinition
 *
 * Task definition for PHPUnit/Pest testing.
 *
 * Main Responsibilities:
 * - Test Runner Configuration: Defines the schema for selecting between Pest and PHPUnit.
 * - Dependency Management: Returns required packages based on the chosen test runner.
 * - Command Execution: Provides the appropriate command to run the selected test suite.
 * - Task Explanation: Provides help text for users regarding test execution.
 *
 * Usage Example:
 * ```php
 * $test = new TestDefinition();
 * $command = $test->commands($config);
 * ```
 */
class TestDefinition extends BaseDefinition
{
    /**
     * Return the schema of the configuration for this SetupDefinitionInterface.
     */
    public function schema(): Schema
    {
        return Expect::anyOf('pest', 'phpunit', '')->default('pest');
    }

    /**
     * Return help text for this config key.
     */
    public function explainConfig(): string
    {
        return "'' = disabled, 'pest' or 'phpunit' = enabled";
    }

    /**
     * Return the list of required packages.
     */
    protected function getPackages(Config $config): Requirements
    {
        /*
         * Check config for test settings.
         */
        $requirements = match ($config->config->test) {
            'pest' => Requirements::dev('pestphp/pest'),
            'phpunit' => Requirements::dev('phpunit/phpunit'),
            default => new Requirements,
        };
        if ($config->config->test !== '') {
            $requirements->addRequireDev('schenke-io/test-output-formatter');
        }

        return $requirements;
    }

    /**
     * Line or lines which will be executed when the script is called.
     */
    protected function getCommands(Config $config): string|array
    {
        /*
         * Check config for test settings and return appropriate command.
         */
        return match ($config->config->test) {
            'pest' => 'vendor/bin/pest',
            'phpunit' => 'vendor/bin/phpunit',
            default => [],
        };
    }

    /**
     * Return help text for task.
     */
    public function explainTask(): string
    {
        return 'run the test suite';
    }
}

<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Requirements;

/**
 * Task definition for PHPStan static analysis.
 *
 * This class manages the configuration for running PHPStan. It handles the
 * requirement for 'phpstan/phpstan' (or 'larastan' for Laravel projects)
 * and provides the command to execute the analysis based on the project's
 * configuration.
 *
 * Implements SetupDefinitionInterface with the following:
 * - schema(): Expects a boolean value to enable/disable analysis
 * - explainConfig(): Describes the configuration key purpose
 * - packages(): Returns appropriate dependencies based on project type (app vs package)
 * - commands(): Provides the CLI command for running PHPStan
 * - explainTask(): Provides the text shown in the task
 */
class AnalyseDefinition extends BaseDefinition
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
        return 'false = disabled, true = enabled (uses phpstan/phpstan-phpunit or larastan/larastan)';
    }

    /**
     * return the list of required packages
     */
    protected function getPackages(Config $config): Requirements
    {
        if (($config->projectContext->sourceRoot ?? 'src') == 'app') {
            /**
             * for Laravel projects use larastan
             */
            return Requirements::dev('larastan/larastan');
        } else {
            /**
             * for other projects use phpstan-phpunit
             */
            return Requirements::dev('phpstan/phpstan-phpunit');
        }
    }

    /**
     * line or lines which will be executed when the script is called
     */
    protected function getCommands(Config $config): string|array
    {
        /**
         * return the standard phpstan analyse command
         */
        return './vendor/bin/phpstan analyse';
    }

    /**
     * return help text for task
     */
    public function explainTask(): string
    {
        // text shown in the task for this task
        return 'run static analysis with PHPStan';
    }
}

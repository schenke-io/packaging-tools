<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Requirements;

/**
 * Class AnalyseDefinition
 *
 * Task definition for PHPStan static analysis.
 *
 * Main Responsibilities:
 * - Schema Definition: Defines the configuration for enabling PHPStan analysis.
 * - Dependency Resolution: Selects between Larastan or PHPStan-PHPUnit based on project type.
 * - Command Generation: Provides the standard CLI command for running analysis.
 * - Project Context: Uses source root to distinguish between Laravel projects and packages.
 *
 * Usage Example:
 * ```php
 * $analyse = new AnalyseDefinition();
 * $packages = $analyse->packages($config);
 * ```
 */
class AnalyseDefinition extends BaseDefinition
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
        return 'false = disabled, true = enabled (uses phpstan/phpstan-phpunit or larastan/larastan)';
    }

    /**
     * Return the list of required packages.
     */
    protected function getPackages(Config $config): Requirements
    {
        if (($config->projectContext->sourceRoot ?? 'src') == 'app') {
            /*
             * For Laravel projects use larastan.
             */
            return Requirements::dev('larastan/larastan');
        } else {
            /*
             * For other projects use phpstan-phpunit.
             */
            return Requirements::dev('phpstan/phpstan-phpunit');
        }
    }

    /**
     * Line or lines which will be executed when the script is called.
     */
    protected function getCommands(Config $config): string|array
    {
        return './vendor/bin/phpstan analyse';
    }

    /**
     * Return help text for task.
     */
    public function explainTask(): string
    {
        return 'run static analysis with PHPStan';
    }
}

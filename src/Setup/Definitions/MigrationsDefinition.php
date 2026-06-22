<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\MigrationHelper;
use SchenkeIo\PackagingTools\Setup\Requirements;

/**
 * Class MigrationsDefinition
 *
 * Task definition for Migration generation and cleaning.
 *
 * Main Responsibilities:
 * - Schema Definition: Defines the configuration for database connection and table selection.
 * - Command Generation: Constructs the Artisan command for generating migrations.
 * - Integration: Incorporates kitloong/laravel-migrations-generator and a custom cleaning step.
 * - Verification: Checks if the required generator package is installed.
 *
 * Usage Example:
 * ```php
 * $migrations = new MigrationsDefinition();
 * $commands = $migrations->commands($config);
 * ```
 */
class MigrationsDefinition extends BaseDefinition
{
    /**
     * Return the schema of the configuration for this SetupDefinitionInterface.
     */
    public function schema(): Schema
    {
        return Expect::anyOf(
            Expect::null(),
            Expect::string()
        )->default(null);
    }

    /**
     * Return help text for this config key.
     */
    public function explainConfig(): string
    {
        return 'null = disabled, connection:* = auto-detect, connection:table1,table2 = enabled (with connection and tables)';
    }

    /**
     * Return the list of required packages.
     */
    protected function getPackages(Config $config): Requirements
    {
        return new Requirements;
    }

    /**
     * Line or lines which will be executed when the script is called.
     */
    protected function getCommands(Config $config): string|array
    {
        if (($config->config->migrations ?? null) === null) {
            return [];
        }

        if (! $this->isMigrationsGeneratorInstalled()) {
            return [
                'echo "##############################################################################"',
                'echo " WARNING: kitloong/laravel-migrations-generator is NOT installed."',
                'echo " Please run: composer require --dev kitloong/laravel-migrations-generator"',
                'echo "##############################################################################"',
            ];
        }

        $resolved = MigrationHelper::resolveMigrationTargets($config, $config->projectContext);

        $command = 'php artisan migrate:generate --no-interaction';
        $command .= ' --tables='.implode(',', $resolved['tables']);

        if ($resolved['connection'] !== '') {
            $command .= " --connection={$resolved['connection']}";
        }

        return [
            $command,
            'SchenkeIo\\PackagingTools\\Setup\\MigrationCleaner::clean',
        ];
    }

    /**
     * Return help text for task.
     */
    public function explainTask(): string
    {
        return 'generate and clean migrations from existing database';
    }

    /**
     * Check if the migrations generator package is installed.
     */
    protected function isMigrationsGeneratorInstalled(): bool
    {
        return class_exists('KitLoong\MigrationsGenerator\MigrationsGeneratorServiceProvider');
    }
}

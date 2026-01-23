<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\MigrationHelper;
use SchenkeIo\PackagingTools\Setup\Requirements;

/**
 * Task definition for Migration generation and cleaning.
 *
 * This task integrates kitloong/laravel-migrations-generator and
 * applies a cleaning step to remove hardcoded database connections.
 */
class MigrationsDefinition extends BaseDefinition
{
    public function schema(): Schema
    {
        return Expect::anyOf(
            Expect::null(),
            Expect::string()
        )->default(null);
    }

    public function explainConfig(): string
    {
        return 'null = disabled, connection:* = auto-detect, connection:table1,table2 = enabled (with connection and tables)';
    }

    protected function getPackages(Config $config): Requirements
    {
        return Requirements::dev('kitloong/laravel-migrations-generator');
    }

    protected function getCommands(Config $config): string|array
    {
        if (($config->config->migrations ?? null) === null) {
            return [];
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

    public function explainTask(): string
    {
        return 'generate and clean migrations from existing database';
    }
}

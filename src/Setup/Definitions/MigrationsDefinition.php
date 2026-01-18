<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
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
        return 'null = disabled, connection:table1,table2 = enabled (with connection and tables)';
    }

    protected function getPackages(Config $config): Requirements
    {
        return Requirements::dev('kitloong/laravel-migrations-generator');
    }

    protected function getCommands(Config $config): string|array
    {
        $val = $config->config->migrations;
        if ($val === null) {
            return [];
        }

        $command = 'php artisan migrate:generate --no-interaction';
        if (is_string($val)) {
            if (str_contains($val, ':')) {
                [$connection, $tables] = explode(':', $val, 2);
                if ($connection) {
                    $command .= " --connection=$connection";
                }
                if ($tables) {
                    $command .= " --tables=$tables";
                }
            } else {
                $command .= " --connection=$val";
            }
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

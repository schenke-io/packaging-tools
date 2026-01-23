<?php

namespace SchenkeIo\PackagingTools\Tests\Unit\Setup\Definitions;

use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Definitions\MigrationsDefinition;
use SchenkeIo\PackagingTools\Setup\ProjectContext;
use SchenkeIo\PackagingTools\Setup\Requirements;

test('schema returns a Schema object', function () {
    $definition = new MigrationsDefinition;
    expect($definition->schema())->toBeInstanceOf(Schema::class);
});

test('explainConfig returns a string', function () {
    $definition = new MigrationsDefinition;
    expect($definition->explainConfig())->toBeString();
});

describe('packages', function () {
    test('returns empty Requirements if migrations is disabled', function () {
        $definition = new MigrationsDefinition;
        $config = new Config(['migrations' => null], new ProjectContext);
        expect($definition->packages($config))->toBeInstanceOf(Requirements::class);
        expect($definition->packages($config)->devPackages)->toBeEmpty();
    });

    test('returns migrations generator Requirements if migrations is enabled', function () {
        $definition = new MigrationsDefinition;
        $config = new Config(['migrations' => 'mysql'], new ProjectContext);
        $requirements = $definition->packages($config);
        expect($requirements)->toBeInstanceOf(Requirements::class);
        expect($requirements->devPackages)->toContain('kitloong/laravel-migrations-generator');
    });
});

describe('commands', function () {
    test('returns empty array if migrations is disabled', function () {
        $definition = new MigrationsDefinition;
        $config = new Config(['migrations' => null], new ProjectContext);
        expect($definition->commands($config))->toBeArray()->toBeEmpty();
    });

    test('returns generate and clean commands if migrations is a connection string', function () {
        $definition = new MigrationsDefinition;
        $config = new Config(['migrations' => 'mysql'], new ProjectContext);
        $commands = $definition->commands($config);
        expect($commands)->toBeArray()->toHaveCount(2);
        expect($commands[0])->toContain('migrate:generate')->toContain('--connection=mysql');
        expect($commands[1])->toBe('SchenkeIo\PackagingTools\Setup\MigrationCleaner::clean');
    });

    test('returns generate and clean commands if migrations is a connection:* string', function () {
        $definition = new MigrationsDefinition;
        $config = new Config(['migrations' => 'mysql:*'], new ProjectContext);
        $commands = $definition->commands($config);
        expect($commands)->toBeArray()->toHaveCount(2);
        expect($commands[0])->toContain('migrate:generate')
            ->toContain('--connection=mysql');
        expect($commands[1])->toBe('SchenkeIo\PackagingTools\Setup\MigrationCleaner::clean');
    });

    test('returns generate and clean commands if migrations is a connection:table string', function () {
        $definition = new MigrationsDefinition;
        $config = new Config(['migrations' => 'mysql:users,posts'], new ProjectContext);
        $commands = $definition->commands($config);
        expect($commands)->toBeArray()->toHaveCount(2);
        expect($commands[0])->toContain('migrate:generate')
            ->toContain('--connection=mysql')
            ->toContain('--tables=batches,cache,cache_locks,failed_jobs,job_batches,jobs,migrations,password_reset_tokens,posts,sessions,users');
        expect($commands[1])->toBe('SchenkeIo\PackagingTools\Setup\MigrationCleaner::clean');
    });

    test('reaches null branch if task name is different', function () {
        $definition = new MigrationsDefinition('pint');
        $config = new Config(['pint' => true, 'migrations' => null], new ProjectContext);
        expect($definition->commands($config))->toBe([]);
    });
});

test('explainTask returns a string', function () {
    $definition = new MigrationsDefinition;
    expect($definition->explainTask())->toBeString();
});

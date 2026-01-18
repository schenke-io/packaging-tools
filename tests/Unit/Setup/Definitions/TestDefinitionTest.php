<?php

use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Definitions\TestDefinition;

it('can get the schema', function () {
    $definition = new TestDefinition;
    $schema = $definition->schema();
    expect($schema)->toBeInstanceOf(Schema::class);
});

it('can explain the config', function () {
    $definition = new TestDefinition;
    expect($definition->explainConfig())->toBeString();
});

describe('packages method', function () {
    it('returns empty requirements by default', function () {
        $definition = new TestDefinition;
        $config = new Config(['test' => false]);
        $requirements = $definition->packages($config);
        expect($requirements->devPackages)->toBeEmpty();
    });

    it('returns pest requirements', function () {
        $definition = new TestDefinition;
        $config = new Config(['test' => 'pest']);
        $requirements = $definition->packages($config);
        expect($requirements->devPackages)->toContain('pestphp/pest');
    });

    it('returns phpunit requirements', function () {
        $definition = new TestDefinition;
        $config = new Config(['test' => 'phpunit']);
        $requirements = $definition->packages($config);
        expect($requirements->devPackages)->toContain('phpunit/phpunit');
    });
});

describe('commands method', function () {
    it('returns empty array by default', function () {
        $definition = new TestDefinition;
        $config = new Config(['test' => false]);
        $commands = $definition->commands($config);
        expect($commands)->toBeArray()->toBeEmpty();
    });

    it('returns pest command', function () {
        $definition = new TestDefinition;
        $config = new Config(['test' => 'pest']);
        $commands = $definition->commands($config);
        expect($commands)->toBe('vendor/bin/pest');
    });

    it('returns phpunit command', function () {
        $definition = new TestDefinition;
        $config = new Config(['test' => 'phpunit']);
        $commands = $definition->commands($config);
        expect($commands)->toBe('vendor/bin/phpunit');
    });
});

it('can explain the use', function () {
    $definition = new TestDefinition;
    expect($definition->explainTask())->toBeString();
});

<?php

use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Definitions\CoverageDefinition;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

test('schema returns a bool schema', function () {
    $definition = new CoverageDefinition;
    expect($definition->schema())->toBeInstanceOf(\Nette\Schema\Schema::class);
});

test('explainConfig returns a string', function () {
    $definition = new CoverageDefinition;
    expect($definition->explainConfig())->toBeString();
});

test('explainTask returns a string', function () {
    $definition = new CoverageDefinition;
    expect($definition->explainTask())->toBeString();
});

test('packages returns empty requirements by default', function () {
    $config = new Config(['coverage' => true, 'test' => 'pest'], new ProjectContext);
    $definition = new CoverageDefinition;
    $requirements = $definition->packages($config);
    expect($requirements->devPackages)->toBeEmpty();
});

test('commands returns pest coverage command if test is pest', function () {
    $config = new Config(['coverage' => true, 'test' => 'pest'], new ProjectContext);
    $definition = new CoverageDefinition;
    $commands = $definition->commands($config);
    expect($commands)->toBe('vendor/bin/pest --coverage');
});

test('commands returns phpunit coverage command if test is phpunit', function () {
    $config = new Config(['coverage' => true, 'test' => 'phpunit'], new ProjectContext);
    $definition = new CoverageDefinition;
    $commands = $definition->commands($config);
    expect($commands)->toBe('vendor/bin/phpunit --coverage');
});

test('commands returns empty array if coverage is disabled', function () {
    $config = new Config(['coverage' => false, 'test' => 'pest'], new ProjectContext);
    $definition = new CoverageDefinition;
    $commands = $definition->commands($config);
    expect($commands)->toBe([]);
});

test('commands returns empty array if test runner is unknown', function () {
    $config = new Config(['coverage' => true, 'test' => ''], new ProjectContext);
    $definition = new CoverageDefinition;
    $commands = $definition->commands($config);
    expect($commands)->toBe([]);
});

<?php

use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Definitions\InfectionDefinition;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

test('schema returns a bool schema', function () {
    $definition = new InfectionDefinition;
    expect($definition->schema())->toBeInstanceOf(\Nette\Schema\Schema::class);
});

test('explainConfig returns a string', function () {
    $definition = new InfectionDefinition;
    expect($definition->explainConfig())->toBeString();
});

test('explainTask returns a string', function () {
    $definition = new InfectionDefinition;
    expect($definition->explainTask())->toBeString();
});

test('packages returns infection package if enabled', function () {
    $config = new Config(['infection' => true], new ProjectContext);

    $definition = new InfectionDefinition;
    $requirements = $definition->packages($config);

    expect($requirements->devPackages)->toBe(['infection/infection']);
});

test('packages returns empty requirements if disabled', function () {
    $config = new Config(['infection' => false], new ProjectContext);

    $definition = new InfectionDefinition;
    $requirements = $definition->packages($config);

    expect($requirements->devPackages)->toBeEmpty();
});

test('commands returns infection command if enabled', function () {
    $config = new Config(['infection' => true], new ProjectContext);

    $definition = new InfectionDefinition;
    $commands = $definition->commands($config);

    expect($commands)->toBe('./vendor/bin/infection');
});

test('commands returns empty array if disabled', function () {
    $config = new Config(['infection' => false], new ProjectContext);

    $definition = new InfectionDefinition;
    $commands = $definition->commands($config);

    expect($commands)->toBe([]);
});

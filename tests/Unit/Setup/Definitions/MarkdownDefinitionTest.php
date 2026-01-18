<?php

use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Definitions\MarkdownDefinition;
use SchenkeIo\PackagingTools\Setup\Requirements;

uses()->group('MarkdownDefinition');

it('can create a schema', function () {
    $definition = new MarkdownDefinition;
    $schema = $definition->schema();
    expect($schema)->toBeInstanceOf(Schema::class);
});

it('can explain the config', function () {
    $definition = new MarkdownDefinition;
    $explanation = $definition->explainConfig();
    expect($explanation)->toBeString();
});

it('can return required packages', function () {
    $definition = new MarkdownDefinition;
    $config = new Config(['markdown' => null]);
    $requirements = $definition->packages($config);
    expect($requirements)->toBeInstanceOf(Requirements::class);
});

it('can return commands when markdown is configured', function () {
    $definition = new MarkdownDefinition;
    $config = new Config(['markdown' => 'echo markdown']);
    $commands = $definition->commands($config);
    expect($commands)->toBe('echo markdown');
});

it('can return empty array when markdown is not configured', function () {
    $definition = new MarkdownDefinition;
    $config = new Config(['markdown' => null]);
    $commands = $definition->commands($config);
    expect($commands)->toBeArray();
    expect($commands)->toBeEmpty();
});

it('can explain the use', function () {
    $definition = new MarkdownDefinition;
    $explanation = $definition->explainTask();
    expect($explanation)->toBeString();
});

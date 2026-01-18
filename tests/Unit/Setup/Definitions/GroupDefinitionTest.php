<?php

use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Definitions\GroupDefinition;
use SchenkeIo\PackagingTools\Setup\Requirements;

it('can create a GroupDefinition instance', function () {
    $tasks = ['task1', 'task2'];
    $groupDefinition = new GroupDefinition($tasks);

    expect($groupDefinition)->toBeInstanceOf(GroupDefinition::class);
});

it('can return the schema', function () {
    $tasks = ['task1', 'task2'];
    $groupDefinition = new GroupDefinition($tasks);
    $schema = $groupDefinition->schema();

    expect($schema)->toBeInstanceOf(Schema::class);
});

it('can return the explain config text', function () {
    $tasks = ['task1', 'task2'];
    $groupDefinition = new GroupDefinition($tasks);
    $explainConfig = $groupDefinition->explainConfig();

    expect($explainConfig)->toBeString();
    expect($explainConfig)->toContain('an array of scripts to include in this group:');
    expect($explainConfig)->toContain('task1');
    expect($explainConfig)->toContain('task2');
});

it('can return the list of required packages', function () {
    $tasks = ['pint', 'test'];
    $groupDefinition = new GroupDefinition($tasks);
    $config = new Config(['pint' => true, 'test' => 'pest']);
    $requirements = $groupDefinition->packages($config);

    expect($requirements)->toBeInstanceOf(Requirements::class);
});

it('can return the commands', function () {
    $tasks = ['pint', 'test'];
    $groupDefinition = new GroupDefinition($tasks);
    $config = new Config(['pint' => true, 'test' => '']);

    $commands = $groupDefinition->commands($config);

    expect($commands)->toBeArray();
    expect($commands)->toContain('@pint');
    expect($commands)->not->toContain('@test');
});

it('returns empty array when the group is empty in config', function () {
    $tasks = ['pint', 'test'];
    $groupDefinition = new GroupDefinition($tasks);
    $groupDefinition->setTaskName('quick');

    $config = new Config(['quick' => [], 'pint' => true, 'test' => 'pest']);

    $commands = $groupDefinition->commands($config);
    expect($commands)->toBeArray()->toBeEmpty();
});

it('can return the explain use text', function () {
    $tasks = ['task1', 'task2'];
    $groupDefinition = new GroupDefinition($tasks);
    $explainUse = $groupDefinition->explainTask();

    expect($explainUse)->toBeString();
    expect($explainUse)->toContain('run all scripts');
    expect($explainUse)->toContain('task1');
    expect($explainUse)->toContain('task2');
});

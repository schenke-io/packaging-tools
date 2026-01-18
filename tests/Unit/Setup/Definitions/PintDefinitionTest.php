<?php

use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Definitions\PintDefinition;
use SchenkeIo\PackagingTools\Setup\Requirements;

uses()->group('PintDefinition');

it('can create a PintDefinition instance', function () {
    $pintDefinition = new PintDefinition;
    expect($pintDefinition)->toBeInstanceOf(PintDefinition::class);
});

it('returns a Schema instance for the configuration schema', function () {
    $pintDefinition = new PintDefinition;
    $schema = $pintDefinition->schema();
    expect($schema)->toBeInstanceOf(Schema::class);
});

it('explains the configuration', function () {
    $pintDefinition = new PintDefinition;
    expect($pintDefinition->explainConfig())->toBeString();
});

it('returns the correct packages based on config', function () {
    $pintDefinition = new PintDefinition;
    $configTrue = new Config(['pint' => true]);
    $configFalse = new Config(['pint' => false]);

    $packagesTrue = $pintDefinition->packages($configTrue);
    expect($packagesTrue)->toBeInstanceOf(Requirements::class);
    expect($packagesTrue->devPackages)->toContain('laravel/pint');

    $packagesFalse = $pintDefinition->packages($configFalse);
    expect($packagesFalse)->toBeInstanceOf(Requirements::class);
    expect($packagesFalse->devPackages)->toBeEmpty();
});

it('returns the correct commands based on config', function () {
    $pintDefinition = new PintDefinition;
    $configTrue = new Config(['pint' => true]);
    $configFalse = new Config(['pint' => false]);

    $commandsTrue = $pintDefinition->commands($configTrue);
    expect($commandsTrue)->toBeString();
    expect($commandsTrue)->toBe('vendor/bin/pint');

    $commandsFalse = $pintDefinition->commands($configFalse);
    expect($commandsFalse)->toBeArray();
    expect($commandsFalse)->toBeEmpty();
});

it('explains the use', function () {
    $pintDefinition = new PintDefinition;
    expect($pintDefinition->explainTask())->toBeString();
});

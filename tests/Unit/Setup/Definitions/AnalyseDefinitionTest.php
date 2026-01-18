<?php

use Nette\Schema\Schema; // Correct namespace
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Definitions\AnalyseDefinition;
use SchenkeIo\PackagingTools\Setup\ProjectContext;
use SchenkeIo\PackagingTools\Setup\Requirements;

test('schema returns a Schema object', function () {
    $analyseDefinition = new AnalyseDefinition;
    expect($analyseDefinition->schema())->toBeInstanceOf(Schema::class);
});

test('explainConfig returns a string', function () {
    $analyseDefinition = new AnalyseDefinition;
    expect($analyseDefinition->explainConfig())->toBeString();
});

describe('packages', function () {
    test('returns empty Requirements if analyse is disabled', function () {
        $analyseDefinition = new AnalyseDefinition;
        $config = new Config(['analyse' => false], new ProjectContext);
        expect($analyseDefinition->packages($config))->toBeInstanceOf(Requirements::class);
        expect($analyseDefinition->packages($config)->devPackages)->toBeEmpty();
    });

    test('returns larastan Requirements if analyse is enabled and sourceRoot is app', function () {
        $analyseDefinition = new AnalyseDefinition;
        $config = new Config(['analyse' => true], new ProjectContext(['sourceRoot' => 'app']));
        $requirements = $analyseDefinition->packages($config);
        expect($requirements)->toBeInstanceOf(Requirements::class);
        expect($requirements->devPackages)->toContain('larastan/larastan');
    });

    test('returns phpstan-phpunit Requirements if analyse is enabled and sourceRoot is not app', function () {
        $analyseDefinition = new AnalyseDefinition;
        $config = new Config(['analyse' => true], new ProjectContext(['sourceRoot' => 'src']));
        $requirements = $analyseDefinition->packages($config);
        expect($requirements)->toBeInstanceOf(Requirements::class);
        expect($requirements->devPackages)->toContain('phpstan/phpstan-phpunit');
    });
});

describe('commands', function () {
    test('returns empty array if analyse is disabled', function () {
        $analyseDefinition = new AnalyseDefinition;
        $config = new Config(['analyse' => false], new ProjectContext);
        expect($analyseDefinition->commands($config))->toBeArray();
        expect($analyseDefinition->commands($config))->toBeEmpty();
    });

    test('returns phpstan analyse command if analyse is enabled', function () {
        $analyseDefinition = new AnalyseDefinition;
        $config = new Config(['analyse' => true], new ProjectContext);
        expect($analyseDefinition->commands($config))->toBeString();
        expect($analyseDefinition->commands($config))->toBe('./vendor/bin/phpstan analyse');
    });
});

test('explainTask returns a string', function () {
    $analyseDefinition = new AnalyseDefinition;
    expect($analyseDefinition->explainTask())->toBeString();
});

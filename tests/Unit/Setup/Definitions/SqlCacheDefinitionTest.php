<?php

use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Definitions\SqlCacheDefinition;
use SchenkeIo\PackagingTools\Setup\ProjectContext;
use SchenkeIo\PackagingTools\Setup\Requirements;

test('schema returns a Schema object', function () {
    $definition = new SqlCacheDefinition;
    expect($definition->schema())->toBeInstanceOf(Schema::class);
});

test('explainConfig returns a string', function () {
    $definition = new SqlCacheDefinition;
    expect($definition->explainConfig())->toBeString();
});

describe('packages', function () {
    test('returns empty Requirements even if enabled', function () {
        $definition = new SqlCacheDefinition;
        $config = new Config(['sql-cache' => true], new ProjectContext);
        expect($definition->packages($config))->toBeInstanceOf(Requirements::class);
        expect($definition->packages($config)->devPackages)->toBeEmpty();
    });
});

describe('commands', function () {
    test('returns empty array if disabled', function () {
        $definition = new SqlCacheDefinition;
        $config = new Config(['sql-cache' => false], new ProjectContext);
        expect($definition->commands($config))->toBeArray();
        expect($definition->commands($config))->toBeEmpty();
    });

    test('returns dump command if enabled', function () {
        $definition = new SqlCacheDefinition;
        $config = new Config(['sql-cache' => true], new ProjectContext);
        expect($definition->commands($config))->toBeArray();
        expect($definition->commands($config))->toContain('SchenkeIo\\PackagingTools\\Setup\\SqlCache::dump');
    });
});

test('explainTask returns a string', function () {
    $definition = new SqlCacheDefinition;
    expect($definition->explainTask())->toBeString();
});

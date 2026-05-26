<?php

pest()->group('unit');
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Definitions\BadgeDefinition;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

test('schema returns a Schema object', function () {
    $definition = new BadgeDefinition;
    expect($definition->schema())->toBeInstanceOf(Schema::class);
});

test('explainConfig returns a string', function () {
    $definition = new BadgeDefinition;
    expect($definition->explainConfig())->toBeString();
});

describe('commands', function () {
    test('returns empty array if disabled', function () {
        $definition = new BadgeDefinition;
        $config = new Config(['badges' => false], new ProjectContext);
        expect($definition->commands($config))->toBeArray()->toBeEmpty();
    });

    test('returns doConfiguration badges command if enabled', function () {
        $definition = new BadgeDefinition;
        $config = new Config(['badges' => true], new ProjectContext);
        expect($definition->commands($config))->toBeArray();
        expect($definition->commands($config))->toContain('SchenkeIo\\PackagingTools\\Setup\\Config::doConfiguration(null, ["badges"])');
    });
});

test('explainTask returns the expected string', function () {
    $definition = new BadgeDefinition;
    expect($definition->explainTask())->toBe('generate SVG badges for project metrics');
});

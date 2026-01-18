<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Exceptions\PackagingToolException;
use SchenkeIo\PackagingTools\Markdown\Pieces\Tables;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

test('getTableFromArray generates correct markdown', function () {
    $tables = new Tables;
    $data = [
        ['Col 1', 'Col 2'],
        ['Val 1', 'Val 2'],
    ];
    $md = $tables->getTableFromArray($data);

    expect($md)->toContain('| Col 1 | Col 2 |')
        ->and($md)->toContain('|-------|-------|')
        ->and($md)->toContain('| Val 1 | Val 2 |');
});

test('getTableFromArray handles empty data', function () {
    $tables = new Tables;
    expect($tables->getTableFromArray([]))->toBe('');
});

test('fromCsvString adds data', function () {
    $tables = new Tables;
    $tables->fromCsvString("A,B\nC,D", ',');

    // We need to use getContent to see the data or use reflection
    $reflection = new ReflectionClass($tables);
    $property = $reflection->getProperty('data');
    $property->setAccessible(true);

    expect($property->getValue($tables))->toBe([['A', 'B'], ['C', 'D']]);
});

test('getContent reads files and merges data', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with('/tmp/composer.json')->andReturn(json_encode(['name' => 'a/b']));
    $filesystem->shouldReceive('get')->with('test.csv')->andReturn("Col1,Col2\nVal1,Val2");

    $projectContext = new ProjectContext($filesystem, '/tmp');

    $tables = new Tables;
    $tables->fromArray([['Initial']]);
    $tables->fromFile('test.csv');

    $md = $tables->getContent($projectContext, '/tmp');

    expect($md)->toContain('Initial')
        ->and($md)->toContain('Col1')
        ->and($md)->toContain('Val1');
});

test('getContent throws exception for unsupported extension', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'a/b']));

    $projectContext = new ProjectContext($filesystem, '/tmp');

    $tables = new Tables;
    $tables->fromFile('test.unknown');

    expect(fn () => $tables->getContent($projectContext, '/tmp'))
        ->toThrow(PackagingToolException::class);
});

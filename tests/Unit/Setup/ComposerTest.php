<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Setup\Composer;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('can initialize Composer', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project', 'scripts' => []]));

    $projectContext = new ProjectContext($filesystem);

    $composer = new Composer($projectContext);
    expect($composer->composer)->toHaveKey('scripts');
});

it('can save composer.json', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project', 'scripts' => []]));
    $filesystem->shouldReceive('put')->once();

    $projectContext = new ProjectContext($filesystem);
    $composer = new Composer($projectContext);
    $composer->save();
});

it('can find packages', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'composer.json')) {
            return json_encode([
                'name' => 'test/project',
                'require' => ['php' => '^8.2'],
                'require-dev' => ['pestphp/pest' => '^3.0'],
            ]);
        }

        return '';
    });

    $projectContext = new ProjectContext($filesystem);

    expect(Composer::packageFound('php', null, $projectContext))->toBeTrue()
        ->and(Composer::packageFound('pestphp/pest', null, $projectContext))->toBeTrue()
        ->and(Composer::packageFound('not/found', null, $projectContext))->toBeFalse()
        ->and(Composer::packageFound('php', 'require', $projectContext))->toBeTrue()
        ->and(Composer::packageFound('php', 'require-dev', $projectContext))->toBeFalse();
});

it('can set commands and packages', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'composer.json')) {
            return json_encode(['name' => 'test/project']);
        }

        return '';
    });

    $projectContext = new ProjectContext($filesystem);
    $composer = new Composer($projectContext);

    $config = new \SchenkeIo\PackagingTools\Setup\Config($projectContext);
    $task = $config->taskRegistry->getTask('test');

    $composer->setCommands('test', $task, $config);
    $composer->setPackages($task, $config);
    $composer->setAddPackages();

    expect($composer->composer['scripts'])->toHaveKey('test')
        ->and($composer->composer['scripts'])->toHaveKey('add');
});

it('detects tools from composer.json', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode([
        'require-dev' => [
            'pestphp/pest' => '^3.0',
            'laravel/pint' => '^1.0',
        ],
    ]));

    $projectContext = new ProjectContext($filesystem);
    $composer = new Composer($projectContext);
    $tools = $composer->getToolsFromComposer();

    expect($tools)->toHaveKey('test', 'pest')
        ->and($tools)->toHaveKey('pint', true)
        ->and($tools)->not->toHaveKey('analyse');
});

it('identifies pending scripts', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'composer.json')) {
            return json_encode(['scripts' => []]);
        }

        return '';
    });

    $projectContext = new ProjectContext($filesystem);
    $composer = new Composer($projectContext);
    $config = new \SchenkeIo\PackagingTools\Setup\Config(['test' => 'pest'], $projectContext);

    $pending = $composer->getPendingScripts($config);

    expect($pending)->toHaveKey('test')
        ->and($pending['test']['status'])->toBe('missing');
});

it('identifies pending packages', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'composer.json')) {
            return json_encode([]);
        }

        return '';
    });

    $projectContext = new ProjectContext($filesystem);
    $composer = new Composer($projectContext);
    $config = new \SchenkeIo\PackagingTools\Setup\Config(['test' => 'pest'], $projectContext);

    $pending = $composer->getPendingPackages($config);

    expect($pending)->toHaveKey('pestphp/pest')
        ->and($pending['pestphp/pest']['key'])->toBe('require-dev');
});

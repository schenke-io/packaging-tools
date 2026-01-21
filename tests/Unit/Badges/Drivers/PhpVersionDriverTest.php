<?php

namespace Tests\Unit\Badges\Drivers;

use Illuminate\Filesystem\Filesystem;
use Mockery;
use SchenkeIo\PackagingTools\Badges\Drivers\PhpVersionDriver;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('gets the correct subject', function () {
    $driver = new PhpVersionDriver;
    expect($driver->getSubject())->toBe('PHP');
});

it('extracts PHP version from composer.json', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode([
        'name' => 'test/project',
        'require' => ['php' => '^8.2'],
    ]));

    $projectContext = new ProjectContext($filesystem);
    $driver = new PhpVersionDriver;

    expect($driver->getStatus($projectContext, 'composer.json'))->toBe('^8.2');
});

it('returns n/a if php requirement is missing', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode([
        'name' => 'test/project',
        'require' => [],
    ]));

    $projectContext = new ProjectContext($filesystem);
    $driver = new PhpVersionDriver;

    expect($driver->getStatus($projectContext, 'composer.json'))->toBe('n/a');
});

it('returns the correct color', function () {
    $projectContext = Mockery::mock(ProjectContext::class);
    $driver = new PhpVersionDriver;
    expect($driver->getColor($projectContext, 'composer.json'))->toBe('777bb4');
});

it('returns the correct Shields.io URL', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));

    $projectContext = new ProjectContext($filesystem);
    $driver = new PhpVersionDriver;

    expect($driver->getUrl($projectContext, 'composer.json'))->toBe('https://img.shields.io/packagist/php-v/test/project');
});

it('returns null for URL if project name is unknown', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'unknown']));

    $projectContext = new ProjectContext($filesystem);
    $driver = new PhpVersionDriver;

    expect($driver->getUrl($projectContext, 'composer.json'))->toBeNull();
});

it('detects composer.json as path', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));

    $projectContext = new ProjectContext($filesystem);
    $driver = new PhpVersionDriver;
    expect($driver->detectPath($projectContext))->toBe('composer.json');
});

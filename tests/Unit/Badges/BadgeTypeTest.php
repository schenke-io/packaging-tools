<?php

namespace Tests\Unit\Badges;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use Mockery;
use SchenkeIo\PackagingTools\Enums\BadgeType;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('can detect paths for various badge types', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(
        json_encode(['name' => 'test/project', 'type' => 'library']), // project root check
        '<phpunit><logging><log type="coverage-clover" target="clover.xml"/></logging></phpunit>', // clover detection
        'parameters: level: 5' // not really needed for detectPath but just in case
    );

    $projectContext = new ProjectContext($filesystem);

    expect(BadgeType::Coverage->detectPath($projectContext))->toBe('clover.xml');
    expect(BadgeType::PhpStan->detectPath($projectContext))->toBe('phpstan.neon');
    expect(BadgeType::Version->detectPath($projectContext))->toBe('composer.json');
});

it('has correct local badge status', function () {
    expect(BadgeType::Coverage->hasLocalBadge())->toBeTrue();
    expect(BadgeType::PhpStan->hasLocalBadge())->toBeTrue();
    expect(BadgeType::Infection->hasLocalBadge())->toBeTrue();
    expect(BadgeType::Version->hasLocalBadge())->toBeFalse();
    expect(BadgeType::Downloads->hasLocalBadge())->toBeFalse();
    expect(BadgeType::Laravel->hasLocalBadge())->toBeFalse();
    expect(BadgeType::Tests->hasLocalBadge())->toBeFalse();
});

it('can detect github workflow path', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('files')->andReturn(['run-tests.yml']);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));

    $projectContext = new ProjectContext($filesystem);

    expect(BadgeType::Tests->detectPath($projectContext))->toBe('run-tests.yml');
});

it('downloads driver works', function () {
    Http::shouldReceive('get')
        ->once()
        ->with('https://img.shields.io/packagist/dt/test/project.json')
        ->andReturn(Mockery::mock(\Illuminate\Http\Client\Response::class, [
            'successful' => true,
            'json' => ['message' => '1.2k', 'color' => 'brightgreen'],
        ]));

    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));

    $projectContext = new ProjectContext($filesystem);
    $driver = BadgeType::Downloads->getDriver();

    expect($driver->getStatus($projectContext, ''))->toBe('1.2k');
});

it('release version driver works', function () {
    Http::shouldReceive('get')
        ->once()
        ->with('https://img.shields.io/packagist/v/test/project.json')
        ->andReturn(Mockery::mock(\Illuminate\Http\Client\Response::class, [
            'successful' => true,
            'json' => ['message' => 'v1.0.0', 'color' => 'blue'],
        ]));

    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));

    $projectContext = new ProjectContext($filesystem);
    $driver = BadgeType::Version->getDriver();

    expect($driver->getStatus($projectContext, ''))->toBe('v1.0.0');
});

it('laravel version driver works', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode([
        'name' => 'test/project',
        'require' => ['laravel/framework' => '^10.0'],
    ]));

    $projectContext = new ProjectContext($filesystem);
    $driver = BadgeType::Laravel->getDriver();

    expect($driver->getStatus($projectContext, ''))->toBe('^10.0');
});

it('github test driver works', function () {
    Http::shouldReceive('get')
        ->twice()
        ->andReturn(Mockery::mock(\Illuminate\Http\Client\Response::class, [
            'successful' => true,
            'json' => ['message' => 'passing', 'color' => 'brightgreen'],
        ]));

    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'owner/repo']));

    $projectContext = new ProjectContext($filesystem);
    $driver = BadgeType::Tests->getDriver();

    expect($driver->getStatus($projectContext, 'run-tests.yml'))->toBe('passing');
    expect($driver->getColor($projectContext, 'run-tests.yml'))->toBe('27AE60');
});

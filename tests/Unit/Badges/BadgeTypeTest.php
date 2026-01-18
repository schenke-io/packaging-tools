<?php

namespace Tests\Unit\Badges;

use Illuminate\Filesystem\Filesystem;
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
    expect(BadgeType::License->detectPath($projectContext))->toBe('composer.json');
});

it('has correct local badge status', function () {
    expect(BadgeType::Coverage->hasLocalBadge())->toBeTrue();
    expect(BadgeType::PhpStan->hasLocalBadge())->toBeTrue();
    expect(BadgeType::Infection->hasLocalBadge())->toBeTrue();
    expect(BadgeType::Version->hasLocalBadge())->toBeFalse();
    expect(BadgeType::Downloads->hasLocalBadge())->toBeFalse();
    expect(BadgeType::Laravel->hasLocalBadge())->toBeFalse();
    expect(BadgeType::Tests->hasLocalBadge())->toBeFalse();
    expect(BadgeType::License->hasLocalBadge())->toBeFalse();
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
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));

    $projectContext = new ProjectContext($filesystem);
    $driver = BadgeType::Downloads->getDriver();

    expect($driver->getStatus($projectContext, ''))->toBe('n/a');
});

it('release version driver works', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));

    $projectContext = new ProjectContext($filesystem);
    $driver = BadgeType::Version->getDriver();

    expect($driver->getStatus($projectContext, ''))->toBe('n/a');
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
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'owner/repo']));

    $projectContext = new ProjectContext($filesystem);
    $driver = BadgeType::Tests->getDriver();

    expect($driver->getStatus($projectContext, 'run-tests.yml'))->toBe('n/a');
    expect($driver->getColor($projectContext, 'run-tests.yml'))->toBe('grey');
});

it('license driver works', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'owner/repo']));

    $projectContext = new ProjectContext($filesystem);
    $driver = BadgeType::License->getDriver();

    expect($driver->getSubject())->toBe('License');
    expect($driver->getStatus($projectContext, ''))->toBe('n/a');
    expect($driver->getColor($projectContext, ''))->toBe('grey');
    expect($driver->getUrl($projectContext, ''))->toBe('https://img.shields.io/github/license/owner/repo');
    expect($driver->getLinkUrl($projectContext, ''))->toBe('https://github.com/owner/repo/blob/main/LICENSE.md');
    expect($driver->detectPath($projectContext))->toBe('composer.json');
});

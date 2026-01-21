<?php

namespace Tests\Unit\Markdown\Pieces;

use Illuminate\Filesystem\Filesystem;
use Mockery;
use SchenkeIo\PackagingTools\Enums\BadgeStyle;
use SchenkeIo\PackagingTools\Markdown\Pieces\Badges;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('can render various badges', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('files')->andReturn([]);

    // For ProjectContext initialization
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::type('string'))->andReturn(json_encode([
        'name' => 'schenke-io/packaging-tools',
    ]));

    $projectContext = new ProjectContext($filesystem);
    $badges = new Badges;

    $badges->version(BadgeStyle::Flat)
        ->download(BadgeStyle::Plastic)
        ->php(BadgeStyle::Flat)
        ->local('Local', 'img/local.png')
        ->forge('hash', 1, 2);

    $content = $badges->getContent($projectContext, 'resources/md');

    expect($content)->toContain('https://img.shields.io/packagist/v/schenke-io/packaging-tools?style=flat')
        ->and($content)->toContain('https://img.shields.io/packagist/dt/schenke-io/packaging-tools?style=plastic')
        ->and($content)->toContain('https://img.shields.io/packagist/php-v/schenke-io/packaging-tools?style=flat')
        ->and($content)->toContain('[![Local](img/local.png)]()')
        ->and($content)->toContain('forge.laravel.com%2Fsite-badges%2Fhash');
});

it('can render test badge', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('files')->andReturn([]);

    // For ProjectContext initialization
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::type('string'))->andReturn(json_encode([
        'name' => 'schenke-io/packaging-tools',
    ]));

    $projectContext = new ProjectContext($filesystem);
    $badges = new Badges;

    $badges->test('run-tests.yml');

    $content = $badges->getContent($projectContext, 'resources/md');

    expect($content)->toContain('https://github.com/schenke-io/packaging-tools/actions/workflows/run-tests.yml/badge.svg');
});

it('does not duplicate automated badges if manually added', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::type('string'))->andReturn(json_encode([
        'name' => 'schenke-io/packaging-tools',
    ]));

    $projectContext = new ProjectContext($filesystem);
    $badges = new Badges;

    // Manually add version badge
    $badges->version(BadgeStyle::Flat);

    $content = $badges->getContent($projectContext, 'resources/md');

    // count occurrences of version badge
    $occurrences = substr_count($content, 'https://img.shields.io/packagist/v/schenke-io/packaging-tools');
    expect($occurrences)->toBe(1);
    // ensure it used the manual style (flat) instead of default (plastic)
    expect($content)->toContain('https://img.shields.io/packagist/v/schenke-io/packaging-tools?style=flat');
    expect($content)->not()->toContain('https://img.shields.io/packagist/v/schenke-io/packaging-tools?style=plastic');
});

it('does not duplicate test badges', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('files')->andReturn([new \Symfony\Component\Finder\SplFileInfo('.github/workflows/run-tests.yml', '.github/workflows', 'run-tests.yml')]);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $projectContext = new ProjectContext($filesystem);
    $badges = new Badges;
    $badges->test('run-tests.yml');
    $content = $badges->getContent($projectContext, 'resources/md');
    $occurrences = substr_count($content, 'actions/workflows/run-tests.yml/badge.svg');
    expect($occurrences)->toBe(1);
});

it('does not duplicate local badges', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_contains($path, 'composer.json')) {
            return json_encode(['name' => 'test/project']);
        }
        if (str_contains($path, 'phpunit.xml')) {
            return '<clover outputFile="clover.xml"/>';
        }

        return '';
    });
    $projectContext = new ProjectContext($filesystem);
    $badges = new Badges;
    // local badge for coverage
    $badges->local('Coverage', 'resources/md/svg/coverage.svg');
    $content = $badges->getContent($projectContext, 'resources/md');
    $occurrences = substr_count($content, 'resources/md/svg/coverage.svg');
    expect($occurrences)->toBe(1);
});

it('handles unknown project name in version and downloads', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturnUsing(fn ($path) => str_contains($path, 'composer.json'));
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'unknown']));
    $projectContext = new ProjectContext($filesystem);
    $badges = new Badges;
    $badges->version()->download();
    $content = $badges->getContent($projectContext, 'resources/md');
    expect($content)->toBe('');
});

it('handles invalid badge type in match', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturnUsing(fn ($path) => str_contains($path, 'composer.json'));
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $projectContext = new ProjectContext($filesystem);
    $badges = new Badges;

    $reflection = new \ReflectionProperty(Badges::class, 'badgeBuffer');
    $reflection->setAccessible(true);
    $reflection->setValue($badges, [['type' => 'invalid', 'args' => []]]);

    $content = $badges->getContent($projectContext, 'resources/md');
    // should not crash and we expect it to hit the default case
    expect(true)->toBeTrue();
});

it('handles missing composer.json in version and download', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    // return true for ProjectContext construction, then false for detectPath
    $filesystem->shouldReceive('exists')->andReturn(true, false, false);
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $projectContext = new ProjectContext($filesystem);
    $badges = new Badges;
    $badges->version()->download();
    $content = $badges->getContent($projectContext, 'resources/md');
    expect($content)->toBe('');
});

it('handles missing URL from driver in download', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    // return true only for composer.json, false for others (like phpunit.xml, phpstan.neon etc)
    $filesystem->shouldReceive('exists')->andReturnUsing(fn ($path) => str_contains($path, 'composer.json'));
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    // return name 'unknown' to make getUrl return null
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'unknown']));
    $projectContext = new ProjectContext($filesystem);
    $badges = new Badges;
    $badges->download();
    $content = $badges->getContent($projectContext, 'resources/md');
    expect($content)->toBe('');
});

it('handles missing PHP requirement in renderPhp', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    // detectPath for PHP driver will look for composer.json
    $filesystem->shouldReceive('exists')->andReturnUsing(fn ($path) => str_contains($path, 'composer.json'));
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    // return name 'unknown' to make getUrl return null (as it happens when repo info is missing)
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'unknown']));
    $projectContext = new ProjectContext($filesystem);
    $badges = new Badges;
    $badges->php();
    $content = $badges->getContent($projectContext, 'resources/md');
    expect($content)->toBe('');
});

it('handles missing composer.json in renderPhp', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'composer.json')) {
            static $count = 0;

            return ++$count === 1;
        }

        return false;
    });
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $projectContext = new ProjectContext($filesystem);
    $badges = new Badges;
    $badges->php();

    $content = $badges->getContent($projectContext, 'resources/md');
    expect($content)->toBe('');
});

<?php

namespace Tests\Unit\Markdown\Pieces;

use Illuminate\Filesystem\Filesystem;
use Mockery;
use SchenkeIo\PackagingTools\Enums\BadgeStyle;
use SchenkeIo\PackagingTools\Markdown\Pieces\Badges;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('skips manual badges when type is unknown', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturnUsing(fn ($p) => str_contains($p, 'composer.json'));
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('allFiles')->andReturn([]);
    $projectContext = new ProjectContext($filesystem);

    $badges = new Badges;
    // Inject unknown badge type into buffer via reflection
    $reflection = new \ReflectionClass($badges);
    $property = $reflection->getProperty('badgeBuffer');
    $property->setAccessible(true);
    $property->setValue($badges, [['type' => 'unknown', 'args' => []]]);

    $content = $badges->getContent($projectContext, '');
    // unknown badge should be skipped
    expect($content)->not->toContain('unknown');
});

it('does not add style for github.com urls', function () {
    $badges = new Badges;
    $reflection = new \ReflectionClass($badges);
    $method = $reflection->getMethod('addStyleToUrl');
    $method->setAccessible(true);

    $url = 'https://github.com/user/repo/badge.svg';
    $result = $method->invoke($badges, $url, BadgeStyle::Flat);

    expect($result)->toBe($url);
});

it('skips automated local badges if they are manually added', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_contains($path, 'composer.json')) {
            return json_encode(['name' => 'test/project']);
        }
        if (str_contains($path, 'phpunit.xml')) {
            return '<clover outputFile="resources/md/svg/coverage.svg"/>';
        }

        return '';
    });
    $filesystem->shouldReceive('files')->andReturn([]);

    $projectContext = new ProjectContext($filesystem);
    $badges = new Badges;
    // manually add local badge for coverage
    $badges->local('Coverage', 'resources/md/svg/coverage.svg');

    $content = $badges->getContent($projectContext, 'resources/md');
    $occurrences = substr_count($content, 'resources/md/svg/coverage.svg');
    expect($occurrences)->toBe(2);
});

it('skips automated test badges if they are manually added', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $filesystem->shouldReceive('files')->andReturn(['run-tests.yml']);

    $projectContext = new ProjectContext($filesystem);
    $badges = new Badges;
    // manually add test badge
    $badges->test('run-tests.yml');

    $content = $badges->getContent($projectContext, 'resources/md');
    $occurrences = substr_count($content, 'actions/workflows/run-tests.yml/badge.svg');
    expect($occurrences)->toBe(1);
});

it('handles renderVersion when no path is detected', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturnUsing(fn ($p) => ! str_contains($p, '.github') && ! str_contains($p, 'resources/md'));
    $filesystem->shouldReceive('exists')->andReturnUsing(fn ($p) => str_contains($p, 'composer.json') ? true : false);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('allFiles')->andReturn([]);

    $projectContext = new ProjectContext($filesystem);
    $badges = new Badges;
    $badges->version();

    $content = $badges->getContent($projectContext, 'resources/md');
    // We expect the URL because detectPath currently always returns composer.json
    expect($content)->toContain('https://img.shields.io/packagist/v/test/project');
});

it('handles renderDownload when no path is detected', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturnUsing(fn ($p) => ! str_contains($p, '.github') && ! str_contains($p, 'resources/md'));
    $filesystem->shouldReceive('exists')->andReturnUsing(fn ($p) => str_contains($p, 'composer.json') ? true : false);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('allFiles')->andReturn([]);

    $projectContext = new ProjectContext($filesystem);
    $badges = new Badges;
    $badges->download();

    $content = $badges->getContent($projectContext, 'resources/md');
    expect($content)->toContain('https://img.shields.io/packagist/dt/test/project');
});

it('handles renderDownload when no URL is returned by driver', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturnUsing(fn ($p) => ! str_contains($p, '.github') && ! str_contains($p, 'resources/md'));
    $filesystem->shouldReceive('exists')->andReturnUsing(fn ($p) => str_contains($p, 'composer.json') ? true : false);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'unknown'])); // unknown project name -> null URL
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('allFiles')->andReturn([]);

    $projectContext = new ProjectContext($filesystem);
    $badges = new Badges;
    $badges->download();

    $content = $badges->getContent($projectContext, 'resources/md');
    expect($content)->toBe('');
});

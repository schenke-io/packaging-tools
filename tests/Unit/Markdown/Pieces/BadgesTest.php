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
        ->local('Local', 'img/local.png')
        ->forge('hash', 1, 2);

    $content = $badges->getContent($projectContext, 'resources/md');

    expect($content)->toContain('https://img.shields.io/packagist/v/schenke-io/packaging-tools?style=flat')
        ->and($content)->toContain('https://img.shields.io/packagist/dt/schenke-io/packaging-tools?style=plastic')
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

    expect($content)->toContain('https://img.shields.io/github/actions/workflow/status/schenke-io/packaging-tools/run-tests.yml');
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

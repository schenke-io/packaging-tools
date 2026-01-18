<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Mockery;
use SchenkeIo\PackagingTools\Badges\MakeBadge;
use SchenkeIo\PackagingTools\Exceptions\PackagingToolException;
use SchenkeIo\PackagingTools\Markdown\Pieces\Badges;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('covers MakeBadge path detection failures', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturnUsing(fn ($p) => str_contains($p, 'composer.json'));
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $projectContext = new ProjectContext($filesystem);

    expect(fn () => MakeBadge::makeCoverageBadge(null, $projectContext))->toThrow(PackagingToolException::class);
    expect(fn () => MakeBadge::makePhpStanBadge(null, '000000', $projectContext))->toThrow(PackagingToolException::class);
    expect(fn () => MakeBadge::makeInfectionBadge(null, $projectContext))->toThrow(PackagingToolException::class);
});

it('covers Badges renderTest workflow not found', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturnUsing(fn ($p) => ! str_contains($p, '.github/workflows'));
    $filesystem->shouldReceive('exists')->andReturnUsing(fn ($p) => str_contains($p, 'composer.json'));
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $projectContext = new ProjectContext($filesystem);

    $badges = new Badges;
    $badges->test('non-existent.yml');

    expect(fn () => $badges->getContent($projectContext, ''))
        ->toThrow(PackagingToolException::class);
});

it('covers Config writeConfig filesystem put failure', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturnUsing(fn ($p) => str_contains($p, 'composer.json'));
    $filesystem->shouldReceive('get')->andReturn(
        json_encode(['name' => 'test/project'])
    );
    // Mock put to fail
    $filesystem->shouldReceive('put')->andReturn(false);

    $projectContext = new ProjectContext($filesystem);
    $config = new Config(null, $projectContext);

    ob_start();
    Config::$silent = false;
    $config->writeConfig($projectContext);
    $output = ob_get_clean();
    Config::$silent = true;

    expect($output)->toContain('error writing');
});

it('covers MakeBadge auto exception handling', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $projectContext = new ProjectContext($filesystem);

    MakeBadge::auto($projectContext);
    expect(true)->toBeTrue();
});

<?php

namespace SchenkeIo\PackagingTools\Tests\Badges;

use Illuminate\Filesystem\Filesystem;
use Mockery;
use SchenkeIo\PackagingTools\Badges\Drivers\InfectionDriver;
use SchenkeIo\PackagingTools\Badges\MakeBadge;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('can make infection badge from driver', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(
        json_encode(['name' => 'test/project']), // projectContext
        json_encode(['msi' => 85.5])             // InfectionDriver::getMsi
    );
    $projectContext = new ProjectContext($filesystem);

    $badge = MakeBadge::fromDriver(new InfectionDriver, 'infection-log.json', $projectContext);

    // MSI: 86% (rounded)
    // Color for 86% should be 27AE60 (green)
    expect($badge->info())->toBe('MSI badge: 86% / 27AE60');
});

it('handles missing infection log', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(
        json_encode(['name' => 'test/project']),
        ''
    );
    $projectContext = new ProjectContext($filesystem);

    $badge = MakeBadge::fromDriver(new InfectionDriver, 'missing.json', $projectContext);

    expect($badge->info())->toBe('MSI badge: 0% / C0392B');
});

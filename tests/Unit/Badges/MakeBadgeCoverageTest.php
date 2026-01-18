<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Badges\MakeBadge;
use SchenkeIo\PackagingTools\Enums\BadgeType;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('silently skips in auto when error occurs', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project', 'type' => 'library']));
    $projectContext = new ProjectContext($filesystem);

    // Mock BadgeType to return a path that will fail
    // Actually, I can't mock Enum cases easily.
    // But I can make the filesystem throw when get() is called for the driver.

    $filesystem->shouldReceive('get')->andThrow(new \Exception('fail'));
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('allFiles')->andReturn([]);

    MakeBadge::auto($projectContext);
    expect(true)->toBeTrue();
});

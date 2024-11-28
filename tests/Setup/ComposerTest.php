<?php

use SchenkeIo\PackagingTools\Setup\Composer;

it('', function () {
    $filesystem = Mockery::mock(\Illuminate\Filesystem\Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->once()->andReturn('{}');

    $composer = new Composer($filesystem);
});

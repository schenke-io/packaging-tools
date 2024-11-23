<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;

it('can build a markdown', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('get')->once();
    $filesystem->shouldReceive('isDirectory')->once()->andReturn(true);
    $filesystem->shouldReceive('put')->once();

    $mda = new MarkdownAssembler('', '', $filesystem);
    $mda->addMarkdown('');
    $mda->writeMarkdown('');
});

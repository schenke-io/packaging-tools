<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;

it('can build a markdown', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('get')->twice()->andReturn('');
    $filesystem->shouldReceive('exists')->once()->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->once()->andReturn(true);
    $filesystem->shouldReceive('put')->once();

    // set the static filesystem for the tests
    setStaticFileSystem($filesystem);

    $mda = new MarkdownAssembler('');
    $mda->addMarkdown('');
    $mda->writeMarkdown('');
});

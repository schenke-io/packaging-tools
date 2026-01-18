<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('adds comment when cover.png is missing in autoHeader', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project', 'description' => 'test desc']));
    $filesystem->shouldReceive('exists')->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'cover.png')) {
            return false;
        }

        return true;
    });
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('allFiles')->andReturn([]);
    $projectContext = Mockery::mock(ProjectContext::class, [$filesystem])->makePartial();
    $projectContext->shouldReceive('isPublicRepository')->andReturn(true);

    $mda = new MarkdownAssembler('src_dir', $projectContext);
    $mda->autoHeader();

    // Check if the comment is in blocks
    $reflection = new ReflectionClass($mda);
    $property = $reflection->getProperty('blocks');
    $property->setAccessible(true);
    $blocks = $property->getValue($mda);

    expect($blocks)->toContain('<!-- cover.png not found in markdown directory -->');
});

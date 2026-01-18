<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;
use SchenkeIo\PackagingTools\Markdown\Providers\ReflectionProvider;
use SchenkeIo\PackagingTools\Setup\ProjectContext;
use SchenkeIo\PackagingTools\Tests\Unit\Markdown\SampleClass;

it('MarkdownAssembler supports fluid API', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']), 'markdown content');
    $filesystem->shouldReceive('put')->once();
    $projectContext = new ProjectContext($filesystem);

    $mda = new MarkdownAssembler('tests/Data', $projectContext);
    $mda->addMarkdown('test.md')
        ->addText('some text')
        ->addTableOfContents()
        ->addContentProvider(new ReflectionProvider(SampleClass::class));

    ob_start();
    $mda->writeMarkdown('README.md');
    ob_get_clean();

    expect($mda)->toBeInstanceOf(MarkdownAssembler::class);
});

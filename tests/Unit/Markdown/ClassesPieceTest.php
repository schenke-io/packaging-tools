<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;
use SchenkeIo\PackagingTools\Markdown\Pieces\Classes;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('Classes piece can add a class and get content', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_contains($path, 'composer.json')) {
            return json_encode(['name' => 'test/project']);
        }

        return 'markdown content';
    });
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $projectContext = new ProjectContext($filesystem);

    $classes = new Classes;
    $classes->add(MarkdownAssembler::class);
    $md = $classes->getContent($projectContext, 'markdown_dir');

    expect($md)->toContain('### MarkdownAssembler');
});

it('Classes piece can use custom callback', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $projectContext = new ProjectContext($filesystem);

    $classes = new Classes;
    $classes->custom(MarkdownAssembler::class, function ($data) {
        return 'Custom: '.$data['short'];
    });
    $md = $classes->getContent($projectContext, 'markdown_dir');

    expect($md)->toBe('Custom: MarkdownAssembler');
});

it('Classes piece can use glob to add classes', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $reflection = new ReflectionClass(MarkdownAssembler::class);
    $assemblerPath = $reflection->getFileName();

    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) use ($assemblerPath) {
        if (str_contains($path, 'composer.json')) {
            return json_encode(['name' => 'test/project']);
        }
        if ($path == $assemblerPath) {
            return file_get_contents($assemblerPath);
        }

        return 'markdown content';
    });
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('glob')->andReturn([$assemblerPath]);
    $filesystem->shouldReceive('fullPath')->andReturnArg(0);

    $projectContext = new ProjectContext($filesystem);

    $classes = new Classes;
    $classes->glob('src/Markdown/*.php');
    $md = $classes->getContent($projectContext, 'markdown_dir');

    expect($md)->toContain('### MarkdownAssembler');
});

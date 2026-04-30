<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

beforeEach(function () {
    $this->filesystem = Mockery::mock(Filesystem::class);
    $this->filesystem->shouldReceive('isDirectory')->zeroOrMoreTimes()->andReturn(true);
    $this->filesystem->shouldReceive('exists')->zeroOrMoreTimes()->andReturn(true);
    $this->filesystem->shouldReceive('files')->zeroOrMoreTimes()->andReturn([]);
    $this->filesystem->shouldReceive('get')->with(Mockery::type('string'))->zeroOrMoreTimes()->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'composer.json')) {
            return json_encode(['name' => 'test/project', 'description' => 'test description']);
        }

        return 'content';
    });
    $this->projectContext = new ProjectContext($this->filesystem, '/root');
});

it('includes packagist badges by default', function () {
    $this->filesystem->shouldReceive('put')->once()->with(
        Mockery::any(),
        Mockery::on(function ($content) {
            return str_contains($content, '[![Version]') &&
                   str_contains($content, '[![Downloads]') &&
                   str_contains($content, '[![PHP]');
        })
    );

    $mda = new MarkdownAssembler('docs', $this->projectContext);
    $mda->autoHeader();
    $mda->writeMarkdown('README.md');
});

it('omits packagist badges when project is private', function () {
    // This test will fail initially because the constructor doesn't accept the new argument yet
    $mda = new MarkdownAssembler('docs', $this->projectContext, isPrivate: true);

    $this->filesystem->shouldReceive('put')->once()->with(
        Mockery::any(),
        Mockery::on(function ($content) {
            return ! str_contains($content, '[![Version]') &&
                   ! str_contains($content, '[![Downloads]') &&
                   ! str_contains($content, '[![PHP]');
        })
    );

    $mda->autoHeader();
    $mda->writeMarkdown('README.md');
});

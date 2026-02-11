<?php

namespace Tests\Unit\Markdown\Pieces;

use Illuminate\Filesystem\Filesystem;
use Mockery;
use SchenkeIo\PackagingTools\Markdown\Pieces\TOC;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('make safe links', function ($text, $link) {
    $toc = new TOC;
    expect($toc->makeLink($text))->toBe($link);
})->with([
    ['Test Heading', 'test-heading'],
    ['#+ölkasten', 'lkasten'],
    ['$from#', 'from'],
]);

it('can generate a table of contents from blocks', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/test']));
    $projectContext = new ProjectContext($filesystem);

    $toc = new TOC;
    $toc->setBlocks([
        "# Header 1\nSome text here.",
        "## Header 2\nMore text.",
        "### Header 3\nEven more text.",
        '# Another Header 1',
    ]);

    $content = $toc->getContent($projectContext, 'resources/md');

    expect($content)->toBe(
        "* [Header 1](#header-1)\n".
        "  * [Header 2](#header-2)\n".
        "    * [Header 3](#header-3)\n".
        "* [Another Header 1](#another-header-1)\n"
    );
});

it('returns empty string when no blocks are provided', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/test']));
    $projectContext = new ProjectContext($filesystem);

    $toc = new TOC;
    $content = $toc->getContent($projectContext, 'resources/md');

    expect($content)->toBe('');
});

it('ignores headers inside code blocks', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/test']));
    $projectContext = new ProjectContext($filesystem);

    $toc = new TOC;
    $toc->setBlocks([
        '# Real Header',
        "```markdown\n# Fake Header\n```",
        'Some text',
        "```\n## Another Fake Header\n```",
    ]);

    $content = $toc->getContent($projectContext, 'resources/md');

    expect($content)->toBe(
        "* [Real Header](#real-header)\n"
    );
});

it('ignores lines that are not headers', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/test']));
    $projectContext = new ProjectContext($filesystem);

    $toc = new TOC;
    $toc->setBlocks([
        "Just some text\nwithout headers",
        'Maybe a # in the middle of a line',
    ]);

    $content = $toc->getContent($projectContext, 'resources/md');

    expect($content)->toBe('');
});

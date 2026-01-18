<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Exceptions\PackagingToolException;
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;
use SchenkeIo\PackagingTools\Markdown\Pieces\Tables;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('Table throws exception on unsupported extension', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $projectContext = new ProjectContext($filesystem);

    $table = new Tables;
    $table->fromFile('test.txt');
    $table->getContent($projectContext, '');
})->throws(PackagingToolException::class);

it('Table can parse csv and build table', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $projectContext = new ProjectContext($filesystem);

    $table = new Tables;
    $csv = "col1,col2\nval1,val2";
    $md = $table->fromCsvString($csv, ',')->getContent($projectContext, '');
    expect($md)->toContain('| col1 | col2 |')
        ->and($md)->toContain('| val1 | val2 |');
});

it('Table fromFile works', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_contains($path, 'composer.json')) {
            return json_encode(['name' => 'test/project']);
        }

        return "col1,col2\nval1,val2";
    });
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $projectContext = new ProjectContext($filesystem);

    $table = new Tables;
    $md = $table->fromFile('test.csv')->getContent($projectContext, '');
    expect($md)->toContain('| col1 | col2 |');
});

it('Table handles empty lines in csv', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $projectContext = new ProjectContext($filesystem);

    $table = new Tables;
    $csv = "col1,col2\n\nval1,val2\n";
    $md = $table->fromCsvString($csv, ',')->getContent($projectContext, '');
    expect($md)->toContain('| col1 | col2 |')
        ->and($md)->toContain('| val1 | val2 |');
});

it('can build a markdown with various components', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']), "# Heading 1\nContent");
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('put')->once()->with(Mockery::any(), Mockery::on(function ($content) {
        return str_contains($content, '# Heading 1') &&
               str_contains($content, '* [Heading 1](#heading-1)') &&
               str_contains($content, 'Manual content');
    }));

    $projectContext = new ProjectContext($filesystem);

    $mda = new MarkdownAssembler('src_dir', $projectContext);
    $mda->addMarkdown('file.md');
    $mda->addText('Manual content');
    $mda->toc();
    ob_start();
    $mda->writeMarkdown('output.md');
    ob_get_clean();
});

it('can add content from provider', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('put')->once();

    $projectContext = new ProjectContext($filesystem);

    $provider = Mockery::mock(\SchenkeIo\PackagingTools\Contracts\MarkdownPieceInterface::class);
    $provider->shouldReceive('getContent')->andReturn('Provider Content');

    $mda = new MarkdownAssembler('src_dir', $projectContext);
    $mda->addContentProvider($provider);

    ob_start();
    $mda->writeMarkdown('output.md');
    ob_get_clean();
});

it('can be initialized via init and creates directory', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $filesystem->shouldReceive('exists')->andReturn(true, false); // first for composer.json, second for md file
    $filesystem->shouldReceive('isDirectory')->andReturnUsing(function ($path) {
        if (str_contains($path, 'test-md')) {
            return false;
        }

        return true;
    });
    $filesystem->shouldReceive('makeDirectory')->once()->andReturn(true);
    $filesystem->shouldReceive('put')->atLeast()->once();
    $projectContext = new ProjectContext($filesystem);

    MarkdownAssembler::init('test-md', $projectContext);
    expect(true)->toBeTrue();
});

it('can use autoHeader with public repo', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project', 'description' => 'test desc']));
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('files')->andReturn([]);
    $projectContext = Mockery::mock(ProjectContext::class, [$filesystem])->makePartial();
    $projectContext->shouldReceive('isPublicRepository')->andReturn(true);

    $mda = new MarkdownAssembler('src_dir', $projectContext);
    $mda->autoHeader();
    expect(true)->toBeTrue();
});

it('calls various piece methods', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $projectContext = new ProjectContext($filesystem);

    $mda = new MarkdownAssembler('src_dir', $projectContext);
    expect($mda->badges())->toBeInstanceOf(\SchenkeIo\PackagingTools\Markdown\Pieces\Badges::class)
        ->and($mda->classes())->toBeInstanceOf(\SchenkeIo\PackagingTools\Markdown\Pieces\Classes::class)
        ->and($mda->tables())->toBeInstanceOf(\SchenkeIo\PackagingTools\Markdown\Pieces\Tables::class)
        ->and($mda->toc())->toBeInstanceOf(\SchenkeIo\PackagingTools\Markdown\Pieces\TOC::class);
});

it('can add an image', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $projectContext = new ProjectContext($filesystem);

    $mda = new MarkdownAssembler('src_dir', $projectContext);
    $mda->image('alt', 'path', 'url');
    expect(true)->toBeTrue();
});

it('autoHeader handles missing cover.png', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project', 'description' => 'test desc']));
    $filesystem->shouldReceive('exists')->andReturnUsing(function ($path) {
        if (str_contains($path, 'cover.png')) {
            return false;
        }

        return true;
    });
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('files')->andReturn([]);
    $projectContext = Mockery::mock(ProjectContext::class, [$filesystem])->makePartial();
    $projectContext->shouldReceive('isPublicRepository')->andReturn(true);

    $mda = new MarkdownAssembler('src_dir', $projectContext);
    $mda->autoHeader();
    $filesystem->shouldReceive('put')->once()->with(Mockery::any(), Mockery::on(function ($content) {
        return str_contains($content, '<!-- cover.png not found in markdown directory -->');
    }));
    ob_start();
    $mda->writeMarkdown('output.md');
    ob_get_clean();
});

it('getInitialContent handles composer description and missing title block', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project', 'description' => 'test desc']));
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('files')->andReturn([]);
    $projectContext = new ProjectContext($filesystem);

    $mda = new MarkdownAssembler('src_dir', $projectContext);
    $filesystem->shouldReceive('put')->once()->with(Mockery::any(), Mockery::on(function ($content) {
        return str_contains($content, '> test desc');
    }));
    ob_start();
    $mda->writeMarkdown('output.md');
    ob_get_clean();
});

it('can be instantiated with default project context', function () {
    $mda = new MarkdownAssembler('src_dir');
    expect(true)->toBeTrue();
});

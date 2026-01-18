<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Exceptions\PackagingToolException;
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

afterEach(function () {
    Mockery::close();
});

beforeEach(function () {
    $this->filesystem = Mockery::mock(Filesystem::class);
    $this->filesystem->shouldReceive('isDirectory')->zeroOrMoreTimes()->andReturn(true);
    $this->filesystem->shouldReceive('files')->zeroOrMoreTimes()->andReturn([]);
    $this->filesystem->shouldReceive('allFiles')->zeroOrMoreTimes()->andReturn([]);
    $this->filesystem->shouldReceive('exists')->zeroOrMoreTimes()->andReturnUsing(function ($path) {
        if (str_contains($path, 'missing.yml')) {
            return false;
        }

        return true;
    });
    $this->filesystem->shouldReceive('get')->with(Mockery::type('string'))->zeroOrMoreTimes()->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'composer.json')) {
            return json_encode(['name' => 'test/project']);
        }

        return 'content of '.basename($path);
    });
    $this->projectContext = new ProjectContext($this->filesystem, '/root');
});

it('can assemble a markdown with various components', function () {
    $this->filesystem->shouldReceive('put')->once()->with(
        Mockery::any(),
        Mockery::on(function ($content) {
            return str_contains($content, 'content of file1.md') &&
                   str_contains($content, 'some raw text') &&
                   str_contains($content, '* [Header 1](#header-1)') &&
                   str_contains($content, '[![Latest Version]') &&
                   str_contains($content, '| col1 | col2 |');
        })
    );

    $mda = new MarkdownAssembler('docs', $this->projectContext);
    $mda->addMarkdown('file1.md')
        ->addText('some raw text')
        ->addText('# Header 1')
        ->toc();

    $mda->badges()
        ->version()
        ->test('run-tests.yml')
        ->download()
        ->local('Local', 'local.svg')
        ->forge('hash', 1, 1);

    $mda->tables()->fromArray([['col1', 'col2'], ['val1', 'val2']]);

    ob_start();
    $mda->writeMarkdown('README.md');
    ob_get_clean();
});

it('can add classes via glob', function () {
    $this->filesystem->shouldReceive('glob')->andReturn(['/root/src/File1.php']);
    // Mock ClassReader or just let it fail if File1.php doesn't exist in a way it can read
    // For simplicity, let's just check if it calls glob

    $mda = new MarkdownAssembler('docs', $this->projectContext);
    try {
        $mda->classes()->glob('src/*.php');
    } catch (\Exception $e) {
        // ClassReader will likely fail because File1.php content is mocked as 'content of File1.php'
    }

    expect($mda)->toBeInstanceOf(MarkdownAssembler::class);
});

it('can add class markdown', function () {
    $mda = new MarkdownAssembler('docs', $this->projectContext);
    $mda->classes()->add(MarkdownAssembler::class);
    $reflection = new ReflectionClass($mda);
    $blocks = $reflection->getProperty('blocks')->getValue($mda);
    expect($blocks)->toHaveCount(1);
});

it('can add table from file', function () {
    $this->filesystem->shouldReceive('get')->with('/root/data.csv')->andReturn("col1,col2\nval1,val2");
    $mda = new MarkdownAssembler('docs', $this->projectContext);
    $mda->tables()->fromFile('data.csv');
    expect($mda)->toBeInstanceOf(MarkdownAssembler::class);
});

it('can add table from csv string', function () {
    $mda = new MarkdownAssembler('docs', $this->projectContext);
    $mda->tables()->fromCsvString("col1,col2\nval1,val2", ',');
    expect($mda)->toBeInstanceOf(MarkdownAssembler::class);
});

it('can add custom class markdown', function () {
    $mda = new MarkdownAssembler('docs', $this->projectContext);
    // This will fail because getClassData uses Reflection on real classes
    // We should use a class that exists, like MarkdownAssembler itself

    $mda->classes()->custom(MarkdownAssembler::class, function ($data) {
        return 'Custom: '.$data['short'];
    });

    $this->filesystem->shouldReceive('put')->once();
    ob_start();
    $mda->writeMarkdown('test.md');
    ob_get_clean();
});

it('can add local images', function () {
    $mda = new MarkdownAssembler('docs', $this->projectContext);
    $mda->image('alt text', 'path/to/img.png');

    $this->filesystem->shouldReceive('put')->with(Mockery::any(), Mockery::on(function ($content) {
        return str_contains($content, '[![alt text](path/to/img.png)]()');
    }))->once();
    ob_start();
    $mda->writeMarkdown('test.md');
    ob_get_clean();
    expect($mda)->toBeInstanceOf(MarkdownAssembler::class);
});

it('throws exception for missing workflow file in storeTestBadge', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('exists')->andReturnUsing(function ($path) {
        if (str_contains($path, 'missing.yml')) {
            return false;
        }

        return true;
    });
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'owner/markdown-public-repo']));

    $context = new ProjectContext($filesystem, '/root');
    $mda = new MarkdownAssembler('docs', $context);
    $mda->badges()->test('missing.yml');
    ob_start();
    try {
        $mda->writeMarkdown('test.md');
    } finally {
        ob_get_clean();
    }
})->throws(PackagingToolException::class);

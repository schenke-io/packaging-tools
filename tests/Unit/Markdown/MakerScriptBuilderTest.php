<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Markdown\MakerScriptBuilder;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

beforeEach(function () {
    $this->filesystem = Mockery::mock(Filesystem::class);
    $this->filesystem->shouldReceive('isDirectory')->with('/root')->andReturn(true);
    $this->filesystem->shouldReceive('exists')->with('/root/composer.json')->andReturn(true);
    $this->filesystem->shouldReceive('get')->with('/root/composer.json')->andReturn(json_encode(['name' => 'test/project', 'type' => 'project']));
});

it('builds a console command for app project with components', function () {
    $projectContext = new ProjectContext($this->filesystem, '/root', 'app');
    $this->filesystem->shouldReceive('isDirectory')->with('/root/workbench/resources/md')->andReturn(false);
    $this->filesystem->shouldReceive('isDirectory')->with('/root/resources/md')->andReturn(true);

    $file1 = Mockery::mock(\Symfony\Component\Finder\SplFileInfo::class);
    $file1->shouldReceive('getExtension')->andReturn('md');
    $file1->shouldReceive('getFilename')->andReturn('header.md');

    $file2 = Mockery::mock(\Symfony\Component\Finder\SplFileInfo::class);
    $file2->shouldReceive('getExtension')->andReturn('md');
    $file2->shouldReceive('getFilename')->andReturn('usage.md');

    $file3 = Mockery::mock(\Symfony\Component\Finder\SplFileInfo::class);
    $file3->shouldReceive('getExtension')->andReturn('txt');

    $file4 = Mockery::mock(\Symfony\Component\Finder\SplFileInfo::class);
    $file4->shouldReceive('getExtension')->andReturn('md');
    $file4->shouldReceive('getFilename')->andReturn('other.md');

    $this->filesystem->shouldReceive('files')->with('/root/resources/md')->andReturn([$file1, $file2, $file3, $file4]);

    $this->filesystem->shouldReceive('isDirectory')->with('/root/app/Console/Commands')->andReturn(true);
    $this->filesystem->shouldReceive('put')->once()->with('/root/app/Console/Commands/MakeMarkdown.php', Mockery::on(function ($content) {
        return str_contains($content, '->addMarkdown(\'header.md\')') &&
               str_contains($content, '->badges()->all()') &&
               str_contains($content, '->addMarkdown(\'usage.md\')') &&
               str_contains($content, '->classes()->all()') &&
               str_contains($content, '->addMarkdown(\'other.md\')');
    }));

    $builder = new MakerScriptBuilder($projectContext);
    $path = $builder->build();

    expect($path)->toBe('app/Console/Commands/MakeMarkdown.php');
});

it('builds a plain script with components', function () {
    $projectContext = new ProjectContext($this->filesystem, '/root', 'src');
    $this->filesystem->shouldReceive('isDirectory')->with('/root/workbench/resources/md')->andReturn(false);
    $this->filesystem->shouldReceive('isDirectory')->with('/root/resources/md')->andReturn(true);
    $this->filesystem->shouldReceive('isDirectory')->with('/root/workbench/app')->andReturn(false);

    $file1 = Mockery::mock(\Symfony\Component\Finder\SplFileInfo::class);
    $file1->shouldReceive('getExtension')->andReturn('md');
    $file1->shouldReceive('getFilename')->andReturn('header.md');

    $file2 = Mockery::mock(\Symfony\Component\Finder\SplFileInfo::class);
    $file2->shouldReceive('getExtension')->andReturn('md');
    $file2->shouldReceive('getFilename')->andReturn('usage.md');

    $file3 = Mockery::mock(\Symfony\Component\Finder\SplFileInfo::class);
    $file3->shouldReceive('getExtension')->andReturn('md');
    $file3->shouldReceive('getFilename')->andReturn('other.md');

    $this->filesystem->shouldReceive('files')->with('/root/resources/md')->andReturn([$file1, $file2, $file3]);

    $this->filesystem->shouldReceive('put')->once()->with('/root/'.MakerScriptBuilder::PLAIN_SCRIPT, Mockery::on(function ($content) {
        return str_contains($content, '->addMarkdown(\'header.md\')') &&
               str_contains($content, '->badges()->all()') &&
               str_contains($content, '->addMarkdown(\'usage.md\')') &&
               str_contains($content, '->classes()->all()') &&
               str_contains($content, '->addMarkdown(\'other.md\')');
    }));

    $builder = new MakerScriptBuilder($projectContext);
    $path = $builder->build();

    expect($path)->toBe(MakerScriptBuilder::PLAIN_SCRIPT);
});

it('builds a console command for workbench project', function () {
    $projectContext = new ProjectContext($this->filesystem, '/root', 'src');
    $this->filesystem->shouldReceive('isDirectory')->with('/root/workbench/resources/md')->andReturn(true);
    $this->filesystem->shouldReceive('files')->with('/root/workbench/resources/md')->andReturn([]);
    $this->filesystem->shouldReceive('isDirectory')->with('/root/workbench/app')->andReturn(true);
    $this->filesystem->shouldReceive('isDirectory')->with('/root/workbench/app/Console/Commands')->andReturn(false);
    $this->filesystem->shouldReceive('makeDirectory')->with('/root/workbench/app/Console/Commands', 0755, true);
    $this->filesystem->shouldReceive('put')->once()->with('/root/workbench/app/Console/Commands/MakeMarkdown.php', Mockery::any());

    $builder = new MakerScriptBuilder($projectContext);
    $path = $builder->build();

    expect($path)->toBe('workbench/app/Console/Commands/MakeMarkdown.php');
});

it('builds a plain script for other projects', function () {
    $projectContext = new ProjectContext($this->filesystem, '/root', 'src');
    $this->filesystem->shouldReceive('isDirectory')->with('/root/workbench/resources/md')->andReturn(false);
    $this->filesystem->shouldReceive('isDirectory')->with('/root/resources/md')->andReturn(false);
    $this->filesystem->shouldReceive('isDirectory')->with('/root/workbench/app')->andReturn(false);
    $this->filesystem->shouldReceive('put')->once()->with('/root/'.MakerScriptBuilder::PLAIN_SCRIPT, Mockery::any());

    $builder = new MakerScriptBuilder($projectContext);
    $path = $builder->build();

    expect($path)->toBe(MakerScriptBuilder::PLAIN_SCRIPT);
});

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

it('builds a console command for app project', function () {
    $projectContext = new ProjectContext($this->filesystem, '/root', 'app');
    $this->filesystem->shouldReceive('isDirectory')->with('/root/workbench/resources/md')->andReturn(false);
    $this->filesystem->shouldReceive('isDirectory')->with('/root/resources/md')->andReturn(true);
    $this->filesystem->shouldReceive('files')->with('/root/resources/md')->andReturn([]);
    $this->filesystem->shouldReceive('isDirectory')->with('/root/app/Console/Commands')->andReturn(true);
    $this->filesystem->shouldReceive('put')->once()->with('/root/app/Console/Commands/MakeMarkdown.php', Mockery::any());

    $builder = new MakerScriptBuilder($projectContext);
    $path = $builder->build();

    expect($path)->toBe('app/Console/Commands/MakeMarkdown.php');
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

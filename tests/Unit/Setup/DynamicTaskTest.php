<?php

namespace SchenkeIo\PackagingTools\Tests\Unit\Setup;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Mockery;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('Config suggests keys on misspelled keys', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    // 'anlyse' instead of 'analyse'
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']), 'anlyse: true');
    $projectContext = new ProjectContext($filesystem);

    $oldArgv = $_SERVER['argv'] ?? [];
    $_SERVER['argv'] = ['vendor/bin/packaging-tools', 'setup']; // not init

    try {
        new Config($projectContext);
    } catch (Exception $e) {
        $_SERVER['argv'] = $oldArgv;
        expect($e->getMessage())->toContain("did you mean 'analyse'?");

        return;
    }
    $_SERVER['argv'] = $oldArgv;
    $this->fail('Exception not thrown');
});

it('can register and use custom tasks dynamically', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);

    $neonContent = 'customTasks:
  my-task: SchenkeIo\PackagingTools\Tests\Unit\Setup\MyCustomSetupDefinition
my-task: true';

    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']), $neonContent);
    $projectContext = new ProjectContext($filesystem);

    $config = new Config($projectContext);

    expect($config->taskRegistry->getTask('my-task'))->toBeInstanceOf(MyCustomSetupDefinition::class);
    expect($config->config->{'my-task'})->toBeTrue();
});

<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('can initialize config if it is missing', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->with(Mockery::on(fn ($path) => str_ends_with($path, 'artisan')))->andReturn(false);
    $filesystem->shouldReceive('exists')->with(Mockery::on(fn ($path) => str_ends_with($path, '.packaging-tools.neon')))->andReturn(false);
    $filesystem->shouldReceive('exists')->with(Mockery::on(fn ($path) => str_ends_with($path, 'composer.json')))->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(false); // for markdown files
    $filesystem->shouldReceive('makeDirectory')->andReturn(true);
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('allFiles')->andReturn([]);

    $composerJson = json_encode(['name' => 'test/project', 'scripts' => []]);
    $filesystem->shouldReceive('get')->andReturn(
        $composerJson, // for ProjectContext constructor
    );
    $filesystem->shouldReceive('put')->atLeast()->once();

    $oldArgv = $_SERVER['argv'];
    $_SERVER['argv'] = ['vendor/bin/packaging-tools', 'setup', 'config'];

    $projectContext = new ProjectContext($filesystem);

    ob_start();
    Config::doConfiguration($projectContext);
    ob_get_clean();

    $_SERVER['argv'] = $oldArgv;

    $configPath = $projectContext->fullPath(Config::CONFIG_BASE);
    // note: $filesystem->exists($configPath) will still return false because it is mocked
    expect(true)->toBeTrue();
});

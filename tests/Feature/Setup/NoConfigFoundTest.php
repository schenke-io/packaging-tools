<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('displays the correct message when no config file is found', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->with(Mockery::on(fn ($path) => str_ends_with($path, 'artisan')))->andReturn(false);
    $filesystem->shouldReceive('exists')->with(Mockery::on(fn ($path) => str_ends_with($path, '.packaging-tools.neon')))->andReturn(false);
    $filesystem->shouldReceive('exists')->with(Mockery::on(fn ($path) => str_ends_with($path, 'composer.json')))->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(false); // for markdown files
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('allFiles')->andReturn([]);

    $composerJson = json_encode([
        'name' => 'test/project',
        'type' => 'library',
        'require-dev' => [
            'pestphp/pest' => '^3.0',
        ],
        'scripts' => [
            'test' => 'phpunit',
        ],
    ]);
    $filesystem->shouldReceive('get')->andReturn($composerJson);

    $projectContext = new ProjectContext($filesystem);

    Config::$silent = false;
    $oldArgv = $_SERVER['argv'];
    $_SERVER['argv'] = ['composer', 'setup'];

    ob_start();
    Config::doConfiguration($projectContext);
    $output = ob_get_clean();
    Config::$silent = true;

    $_SERVER['argv'] = $oldArgv;

    /*
     * Current behavior:
     * It might say "run 'composer setup config' to do these changes..."
     * and it might also say "run 'composer setup update' to do these changes..." if it detects missing scripts.
     * But since we want "never change composer" when no config found,
     * it should NOT offer 'composer setup update'.
     */

    expect($output)->toContain("run 'composer setup config' to create a new configuration in '.packaging-tools.neon'");
    expect($output)->not->toContain("run 'composer setup update'");
});

it('creates the config file from composer when running config command and no file exists', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->with(Mockery::on(fn ($path) => str_ends_with($path, 'artisan')))->andReturn(false);
    $filesystem->shouldReceive('exists')->with(Mockery::on(fn ($path) => str_ends_with($path, '.packaging-tools.neon')))->andReturn(false);
    $filesystem->shouldReceive('exists')->with(Mockery::on(fn ($path) => str_ends_with($path, 'composer.json')))->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(false); // for markdown files
    $filesystem->shouldReceive('files')->andReturn([]);
    $filesystem->shouldReceive('allFiles')->andReturn([]);

    $composerJson = json_encode([
        'name' => 'test/project',
        'type' => 'library',
        'require-dev' => [
            'pestphp/pest' => '^3.0',
        ],
    ]);
    $filesystem->shouldReceive('get')->andReturn($composerJson);

    // Expectation for writing the file
    $filesystem->shouldReceive('put')->with(
        Mockery::on(fn ($path) => str_ends_with($path, '.packaging-tools.neon')),
        Mockery::on(fn ($content) => str_contains($content, 'test: pest'))
    )->once();

    $projectContext = new ProjectContext($filesystem);

    Config::$silent = false;
    $oldArgv = $_SERVER['argv'];
    $_SERVER['argv'] = ['composer', 'setup', 'config'];

    ob_start();
    Config::doConfiguration($projectContext);
    $output = ob_get_clean();
    Config::$silent = true;

    $_SERVER['argv'] = $oldArgv;

    expect($output)->toContain('merge these keys from composer.json into .packaging-tools.neon:');
    expect($output)->toContain(' - add test: "pest"');
});

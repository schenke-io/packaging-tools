<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('displays "add" for missing scripts', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'composer.json')) {
            return json_encode([
                'name' => 'test/project',
                'scripts' => [],
            ]);
        }
        if (str_ends_with($path, '.packaging-tools.neon')) {
            return "pint: true\ntest: ''";
        }

        return '';
    });

    $projectContext = new ProjectContext($filesystem);

    ob_start();
    Config::$silent = false;
    Config::doConfiguration($projectContext, []);
    Config::$silent = true;
    $output = ob_get_clean();

    expect($output)->toContain('add script pint');
});

it('does not display "skipped" for different scripts', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'composer.json')) {
            return json_encode([
                'name' => 'test/project',
                'require-dev' => [
                    'laravel/pint' => '^1.0',
                ],
                'scripts' => [
                    'pint' => 'something else',
                ],
            ]);
        }
        if (str_ends_with($path, '.packaging-tools.neon')) {
            return "pint: true\ntest: ''";
        }

        return '';
    });

    $projectContext = new ProjectContext($filesystem);

    ob_start();
    Config::$silent = false;
    Config::doConfiguration($projectContext, []);
    Config::$silent = true;
    $output = ob_get_clean();

    expect($output)->not->toContain('differs from recommendations')
        ->and($output)->toContain('Everything is up to date.');
});

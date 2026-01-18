<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

beforeEach(function () {
    $this->oldArgv = $_SERVER['argv'] ?? [];
});

afterEach(function () {
    $_SERVER['argv'] = $this->oldArgv;
});

it('reports everything up to date when no deltas exist', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'composer.json')) {
            return json_encode([
                'require-dev' => ['pestphp/pest' => '^3.0'],
                'scripts' => ['test' => 'vendor/bin/pest'],
            ]);
        }
        if (str_ends_with($path, '.packaging-tools.neon')) {
            return 'test: pest
quick: false
release: false';
        }

        return '';
    });
    $projectContext = new ProjectContext($filesystem);

    $_SERVER['argv'] = ['vendor/bin/packaging-tools', 'setup'];
    ob_start();
    Config::$silent = false;
    Config::doConfiguration($projectContext);
    Config::$silent = true;
    $output = ob_get_clean();

    expect($output)->toContain('Everything is up to date.');
});

it('reports c2p delta', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'composer.json')) {
            return json_encode([
                'require-dev' => ['pestphp/pest' => '^3.0'],
            ]);
        }
        if (str_ends_with($path, '.packaging-tools.neon')) {
            return 'test: false';
        }

        return '';
    });
    $projectContext = new ProjectContext($filesystem);

    $_SERVER['argv'] = ['vendor/bin/packaging-tools', 'setup'];
    ob_start();
    Config::$silent = false;
    Config::doConfiguration($projectContext);
    Config::$silent = true;
    $output = ob_get_clean();

    expect($output)->toContain("run 'composer setup config' to do these changes in '.packaging-tools.neon':");
});

it('reports p2c script delta', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'composer.json')) {
            return json_encode([
                'require-dev' => ['pestphp/pest' => '^3.0'],
                'scripts' => [],
            ]);
        }
        if (str_ends_with($path, '.packaging-tools.neon')) {
            return 'test: pest';
        }

        return '';
    });
    $projectContext = new ProjectContext($filesystem);

    $_SERVER['argv'] = ['vendor/bin/packaging-tools', 'setup'];
    ob_start();
    Config::$silent = false;
    Config::doConfiguration($projectContext);
    Config::$silent = true;
    $output = ob_get_clean();

    expect($output)->toContain("run 'composer setup update' to do these changes in 'composer.json':")
        ->and($output)->toContain(' - script ');
});

it('reports p2c package delta', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'composer.json')) {
            return json_encode([
                'scripts' => ['test' => 'vendor/bin/pest'],
            ]);
        }
        if (str_ends_with($path, '.packaging-tools.neon')) {
            return 'test: pest';
        }

        return '';
    });
    $projectContext = new ProjectContext($filesystem);

    $_SERVER['argv'] = ['vendor/bin/packaging-tools', 'setup'];
    ob_start();
    Config::$silent = false;
    Config::doConfiguration($projectContext);
    Config::$silent = true;
    $output = ob_get_clean();

    expect($output)->toContain("run 'composer setup update' to do these changes in 'composer.json':")
        ->and($output)->toContain(' - package ');
});

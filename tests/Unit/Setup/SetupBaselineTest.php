<?php

pest()->group('unit');
use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\ProjectContext;
use SchenkeIo\PackagingTools\Setup\SqlCache;

it('does nothing in SqlCache::dump if sql-cache is disabled via null', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($p) => str_ends_with($p, 'composer.json')))->andReturn(json_encode(['name' => 'test/test', 'type' => 'library']));
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($p) => str_ends_with($p, '.packaging-tools.neon')))->andReturn('analyse: true');

    $projectContext = new ProjectContext($filesystem, '.', 'src');

    SqlCache::dump(null, $projectContext);
    expect(true)->toBeTrue();
});

it('does nothing in SqlCache::dump if sql-cache is disabled via false', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($p) => str_ends_with($p, 'composer.json')))->andReturn(json_encode(['name' => 'test/test', 'type' => 'library']));
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($p) => str_ends_with($p, '.packaging-tools.neon')))->andReturn('sql-cache: false');

    $projectContext = new ProjectContext($filesystem, '.', 'src');

    SqlCache::dump(null, $projectContext);
    expect(true)->toBeTrue();
});

it('informs about missing configuration during setup', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->with(Mockery::on(fn ($p) => str_ends_with($p, 'composer.json')))->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($p) => str_ends_with($p, 'composer.json')))->andReturn(json_encode([
        'name' => 'test/test',
        'require-dev' => ['phpstan/phpstan' => '*'],
    ]));

    $projectContext = new ProjectContext($filesystem, '.', 'src');

    // config file not found
    $filesystem->shouldReceive('exists')->with(Mockery::on(fn ($p) => str_ends_with($p, '.packaging-tools.neon')))->andReturn(false);

    Config::$silent = false;
    ob_start();
    Config::doConfiguration($projectContext, []);
    $output = ob_get_clean();
    Config::$silent = true;

    expect($output)->toContain("run 'composer setup config' to create a new configuration in '.packaging-tools.neon':")
        ->and($output)->toContain('analyse');
});

it('identifies discrepancies between composer.json and configuration', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->with(Mockery::on(fn ($p) => str_ends_with($p, 'composer.json')))->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($p) => str_ends_with($p, 'composer.json')))->andReturn(json_encode([
        'name' => 'test/test',
        'require-dev' => ['phpstan/phpstan' => '*'],
    ]));

    $projectContext = new ProjectContext($filesystem, '.', 'src');

    $filesystem->shouldReceive('exists')->with(Mockery::on(fn ($p) => str_ends_with($p, '.packaging-tools.neon')))->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($p) => str_ends_with($p, '.packaging-tools.neon')))->andReturn('analyse: false');

    Config::$silent = false;
    ob_start();
    Config::doConfiguration($projectContext, []);
    $output = ob_get_clean();
    Config::$silent = true;

    expect($output)->toContain("run 'composer setup config' to add these keys to '.packaging-tools.neon':")
        ->and($output)->toContain('analyse');
});

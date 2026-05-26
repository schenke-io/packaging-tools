<?php

namespace SchenkeIo\PackagingTools\Tests\Unit\Setup;

pest()->group('unit');

use Illuminate\Filesystem\Filesystem;
use Mockery;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\MigrationCleaner;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

afterEach(function () {
    Mockery::close();
});

it('removes connection calls from migration files', function () {
    Config::$silent = true;

    $mockFile = Mockery::mock('Symfony\Component\Finder\SplFileInfo');
    $mockFile->shouldReceive('getExtension')->andReturn('php');
    $mockFile->shouldReceive('getRealPath')->andReturn('/path/to/migration.php');
    $mockFile->shouldReceive('getFilename')->andReturn('migration.php');

    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($path) => str_contains($path, 'composer.json')))
        ->andReturn(json_encode(['name' => 'test/pkg']));
    $filesystem->shouldReceive('allFiles')->andReturn([$mockFile]);

    // Test multiple regex variations
    $content = "Schema::connection('mysql')->create('users', function (Blueprint \$table) {
        Schema::connection(\"sqlite\") ->create('posts');
        Schema::connection ( 'other' )->table('roles');
        Schema::connection('db')->
            create('another');
    ";
    $expected = "Schema::create('users', function (Blueprint \$table) {
        Schema::create('posts');
        Schema::table('roles');
        Schema::
            create('another');
    ";

    $filesystem->shouldReceive('get')->with('/path/to/migration.php')->andReturn($content);
    $filesystem->shouldReceive('put')->once()->with('/path/to/migration.php', $expected);
    $filesystem->shouldReceive('chmod')->once()->with('/path/to/migration.php', 0444);

    $projectContext = new ProjectContext($filesystem);

    expect(MigrationCleaner::clean(null, $projectContext))->toBe(1);
});

it('ignores non-php files', function () {
    Config::$silent = true;

    $mockFile = Mockery::mock('Symfony\Component\Finder\SplFileInfo');
    $mockFile->shouldReceive('getExtension')->andReturn('txt');

    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($path) => str_contains($path, 'composer.json')))
        ->andReturn(json_encode(['name' => 'test/pkg']));
    $filesystem->shouldReceive('allFiles')->andReturn([$mockFile]);
    $filesystem->shouldReceive('get')->never();

    $projectContext = new ProjectContext($filesystem);

    expect(MigrationCleaner::clean(null, $projectContext))->toBe(0);
});

it('calls chmod on php files that do not need cleaning', function () {
    Config::$silent = true;

    $mockFile = Mockery::mock('Symfony\Component\Finder\SplFileInfo');
    $mockFile->shouldReceive('getExtension')->andReturn('php');
    $mockFile->shouldReceive('getRealPath')->andReturn('/path/to/clean.php');
    $mockFile->shouldReceive('getFilename')->andReturn('clean.php');

    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($path) => str_contains($path, 'composer.json')))
        ->andReturn(json_encode(['name' => 'test/pkg']));
    $filesystem->shouldReceive('allFiles')->andReturn([$mockFile]);

    $content = "Schema::create('users', function (Blueprint \$table) {});";
    $filesystem->shouldReceive('get')->with('/path/to/clean.php')->andReturn($content);
    $filesystem->shouldReceive('put')->never();
    $filesystem->shouldReceive('chmod')->once()->with('/path/to/clean.php', 0444);

    $projectContext = new ProjectContext($filesystem);

    expect(MigrationCleaner::clean(null, $projectContext))->toBe(0);
});

it('does nothing if directory does not exist', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->with(Mockery::on(fn ($path) => ! str_contains($path, 'database/migrations')))->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($path) => str_contains($path, 'composer.json')))
        ->andReturn(json_encode(['name' => 'test/pkg']));
    $filesystem->shouldReceive('isDirectory')->with(Mockery::on(fn ($path) => str_contains($path, 'database/migrations')))->andReturn(false);
    $filesystem->shouldReceive('allFiles')->never();

    $projectContext = new ProjectContext($filesystem);

    expect(MigrationCleaner::clean(null, $projectContext))->toBe(0);
});

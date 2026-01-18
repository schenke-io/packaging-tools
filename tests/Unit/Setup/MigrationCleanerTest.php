<?php

namespace SchenkeIo\PackagingTools\Tests\Unit\Setup;

use Illuminate\Support\Facades\File;
use Mockery;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\MigrationCleaner;

afterEach(function () {
    File::clearResolvedInstances();
});

it('removes connection calls from migration files', function () {
    Config::$silent = true;

    $mockFile = Mockery::mock('Symfony\Component\Finder\SplFileInfo');
    $mockFile->shouldReceive('getExtension')->andReturn('php');
    $mockFile->shouldReceive('getRealPath')->andReturn('/path/to/migration.php');
    $mockFile->shouldReceive('getFilename')->andReturn('migration.php');

    File::shouldReceive('isDirectory')->andReturn(true);
    File::shouldReceive('allFiles')->andReturn([$mockFile]);

    // Test multiple regex variations
    $content = "Schema::connection('mysql')->create('users', function (Blueprint \$table) {
        Schema::connection(\"sqlite\") ->create('posts');
        Schema::connection ( 'other' )->table('roles');
    ";
    $expected = "Schema::create('users', function (Blueprint \$table) {
        Schema::create('posts');
        Schema::table('roles');
    ";

    File::shouldReceive('get')->with('/path/to/migration.php')->andReturn($content);
    File::shouldReceive('put')->once()->with('/path/to/migration.php', $expected);

    expect(MigrationCleaner::clean())->toBe(1);
});

it('ignores non-php files', function () {
    Config::$silent = true;

    $mockFile = Mockery::mock('Symfony\Component\Finder\SplFileInfo');
    $mockFile->shouldReceive('getExtension')->andReturn('txt');

    File::shouldReceive('isDirectory')->andReturn(true);
    File::shouldReceive('allFiles')->andReturn([$mockFile]);
    File::shouldReceive('get')->never();

    expect(MigrationCleaner::clean())->toBe(0);
});

it('does nothing if directory does not exist', function () {
    File::shouldReceive('isDirectory')->andReturn(false);
    File::shouldReceive('allFiles')->never();

    expect(MigrationCleaner::clean())->toBe(0);
});

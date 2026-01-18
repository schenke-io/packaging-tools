<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Badges\Drivers\PhpStanNeonDriver;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

test('getSubject returns PHPStan', function () {
    $driver = new PhpStanNeonDriver;
    expect($driver->getSubject())->toBe('PHPStan');
});

test('getStatus returns level from neon file', function () {
    $driver = new PhpStanNeonDriver;

    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with('/tmp/composer.json')->andReturn(json_encode(['name' => 'a/b']));

    $projectContext = new ProjectContext($filesystem, '/tmp');

    $neonContent = "parameters:\n  level: 8\n  paths:\n    - src";
    $filesystem->shouldReceive('get')->with('/tmp/phpstan.neon')->andReturn($neonContent);

    expect($driver->getStatus($projectContext, 'phpstan.neon'))->toBe('8');
});

test('getStatus returns dash if no level found', function () {
    $driver = new PhpStanNeonDriver;

    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with('/tmp/composer.json')->andReturn(json_encode(['name' => 'a/b']));

    $projectContext = new ProjectContext($filesystem, '/tmp');

    $neonContent = "parameters:\n  paths:\n    - src";
    $filesystem->shouldReceive('get')->with('/tmp/phpstan.neon')->andReturn($neonContent);

    expect($driver->getStatus($projectContext, 'phpstan.neon'))->toBe('-');
});

test('getColor returns constructor color', function () {
    $driver = new PhpStanNeonDriver('aabbcc');

    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with('/tmp/composer.json')->andReturn(json_encode(['name' => 'a/b']));

    $projectContext = new ProjectContext($filesystem, '/tmp');
    expect($driver->getColor($projectContext, 'phpstan.neon'))->toBe('aabbcc');
});

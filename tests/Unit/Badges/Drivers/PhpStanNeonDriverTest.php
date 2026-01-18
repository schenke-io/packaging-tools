<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Badges\Drivers\PhpStanNeonDriver;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

test('getSubject returns PHPStan', function () {
    $driver = new PhpStanNeonDriver;
    expect($driver->getSubject())->toBe('PHPStan');
});

test('getStatus detects PHPStan level from various indentations', function (string $content, string $expectedLevel) {
    $filesystem = \Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::pattern('/composer\.json$/'))->andReturn(json_encode(['name' => 'a/b']));

    $projectContext = new ProjectContext($filesystem, '/root');

    $filesystem->shouldReceive('get')->with('/root/phpstan.neon')->andReturn($content);

    $driver = new PhpStanNeonDriver;
    expect($driver->getStatus($projectContext, 'phpstan.neon'))->toBe($expectedLevel);
})->with([
    'no indentation' => ["level: 5\nparameters:", '5'],
    'space indentation' => ["parameters:\n  level: 6", '6'],
    'multiple spaces' => ["parameters:\n    level: 7", '7'],
    'no level' => ["parameters:\n  paths:", '-'],
    'commented out' => ["# level: 8\nlevel: 4", '4'],
]);

test('getColor returns constructor color', function () {
    $driver = new PhpStanNeonDriver('aabbcc');
    expect($driver->getSubject())->toBe('PHPStan');
    $projectContext = Mockery::mock(ProjectContext::class);
    expect($driver->getColor($projectContext, ''))->toBe('aabbcc');
});

test('getUrl returns null', function () {
    $driver = new PhpStanNeonDriver;
    $projectContext = Mockery::mock(ProjectContext::class);
    expect($driver->getUrl($projectContext, 'path'))->toBeNull();
});

test('detectPath returns correct path or null', function (array $files, ?string $expected) {
    $filesystem = Mockery::mock(Filesystem::class);
    $projectContext = Mockery::mock(ProjectContext::class);
    $projectContext->filesystem = $filesystem;
    $projectContext->shouldReceive('fullPath')->andReturnUsing(fn ($f) => "/root/$f");

    foreach ($files as $file => $exists) {
        $filesystem->shouldReceive('exists')->with("/root/$file")->andReturn($exists);
    }

    $driver = new PhpStanNeonDriver;
    expect($driver->detectPath($projectContext))->toBe($expected);
})->with([
    'none' => [['phpstan.neon' => false, 'phpstan.neon.dist' => false], null],
    'neon' => [['phpstan.neon' => true, 'phpstan.neon.dist' => false], 'phpstan.neon'],
    'dist' => [['phpstan.neon' => false, 'phpstan.neon.dist' => true], 'phpstan.neon.dist'],
]);

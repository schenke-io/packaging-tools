<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Badges\Drivers\PhpStanNeonDriver;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('detects PHPStan level from various indentations', function (string $content, string $expectedLevel) {
    $filesystem = mock(Filesystem::class);
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

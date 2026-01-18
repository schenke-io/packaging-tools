<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Badges\Drivers\CloverCoverageDriver;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

test('getSubject returns Coverage', function () {
    $driver = new CloverCoverageDriver;
    expect($driver->getSubject())->toBe('Coverage');
});

test('getStatus returns percentage string', function () {
    $driver = new CloverCoverageDriver;

    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with('/tmp/composer.json')->andReturn(json_encode(['name' => 'a/b']));

    $projectContext = new ProjectContext($filesystem, '/tmp');

    $cloverXml = '<project><metrics statements="100" coveredstatements="85"/></project>';
    $filesystem->shouldReceive('get')->with('/tmp/clover.xml')->andReturn($cloverXml);

    expect($driver->getStatus($projectContext, 'clover.xml'))->toBe('85%');
});

test('getColor returns correct colors', function (int $statements, int $covered, string $expectedColor) {
    $driver = new CloverCoverageDriver;

    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with('/tmp/composer.json')->andReturn(json_encode(['name' => 'a/b']));

    $projectContext = new ProjectContext($filesystem, '/tmp');

    $cloverXml = sprintf('<project><metrics statements="%d" coveredstatements="%d"/></project>', $statements, $covered);
    $filesystem->shouldReceive('get')->with('/tmp/clover.xml')->andReturn($cloverXml);

    expect($driver->getColor($projectContext, 'clover.xml'))->toBe($expectedColor);
})->with([
    [100, 95, '27AE60'], // > 90
    [100, 80, 'F1C40F'], // 70-90
    [100, 50, 'C0392B'], // < 70
]);

test('getCoverage returns 0 for empty content', function () {
    $driver = new CloverCoverageDriver;

    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with('/tmp/composer.json')->andReturn(json_encode(['name' => 'a/b']));

    $projectContext = new ProjectContext($filesystem, '/tmp');
    $filesystem->shouldReceive('get')->with('/tmp/clover.xml')->andReturn('');

    expect($driver->getStatus($projectContext, 'clover.xml'))->toBe('0%');
});

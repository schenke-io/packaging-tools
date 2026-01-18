<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Badges\Drivers\InfectionDriver;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

beforeEach(function () {
    $this->filesystem = Mockery::mock(Filesystem::class);
    $this->filesystem->shouldReceive('isDirectory')->andReturn(true);
    $this->filesystem->shouldReceive('exists')->with(Mockery::on(fn ($p) => str_contains($p, 'composer.json')))->andReturn(true);
    $this->filesystem->shouldReceive('get')->with('/tmp/composer.json')->andReturn(json_encode(['name' => 'a/b']));
    $this->projectContext = new ProjectContext($this->filesystem, '/tmp');
});

test('getSubject returns MSI', function () {
    $driver = new InfectionDriver;
    expect($driver->getSubject())->toBe('MSI');
});

test('getStatus returns MSI percentage string', function () {
    $driver = new InfectionDriver;
    $this->filesystem->shouldReceive('get')->with('/tmp/infection.json')->andReturn(json_encode(['msi' => 85.5]));
    expect($driver->getStatus($this->projectContext, 'infection.json'))->toBe('86%');
});

test('getColor returns correct colors', function (float $msi, string $expectedColor) {
    $driver = new InfectionDriver;
    $this->filesystem->shouldReceive('get')->with('/tmp/infection.json')->andReturn(json_encode(['msi' => $msi]));
    expect($driver->getColor($this->projectContext, 'infection.json'))->toBe($expectedColor);
})->with([
    [85, '27AE60'], // > 80
    [70, 'F1C40F'], // 60-80
    [50, 'C0392B'], // < 60
]);

test('getUrl returns null', function () {
    $driver = new InfectionDriver;
    expect($driver->getUrl($this->projectContext, 'path'))->toBeNull();
});

test('detectPath finds infection-report.json', function () {
    $driver = new InfectionDriver;
    $this->filesystem->shouldReceive('exists')->with('/tmp/infection-report.json')->andReturn(true);
    expect($driver->detectPath($this->projectContext))->toBe('infection-report.json');
});

test('detectPath finds build/infection-report.json', function () {
    $driver = new InfectionDriver;
    $this->filesystem->shouldReceive('exists')->with('/tmp/infection-report.json')->andReturn(false);
    $this->filesystem->shouldReceive('exists')->with('/tmp/build/infection-report.json')->andReturn(true);
    expect($driver->detectPath($this->projectContext))->toBe('build/infection-report.json');
});

test('detectPath returns null if no report found', function () {
    $driver = new InfectionDriver;
    $this->filesystem->shouldReceive('exists')->with('/tmp/infection-report.json')->andReturn(false);
    $this->filesystem->shouldReceive('exists')->with('/tmp/build/infection-report.json')->andReturn(false);
    expect($driver->detectPath($this->projectContext))->toBeNull();
});

test('getMsi returns 0 for empty content or missing msi key', function () {
    $driver = new InfectionDriver;

    $this->filesystem->shouldReceive('get')->with('/tmp/empty.json')->andReturn('');
    expect($driver->getStatus($this->projectContext, 'empty.json'))->toBe('0%');

    $this->filesystem->shouldReceive('get')->with('/tmp/missing.json')->andReturn(json_encode([]));
    expect($driver->getStatus($this->projectContext, 'missing.json'))->toBe('0%');
});

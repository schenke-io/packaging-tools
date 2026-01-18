<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Badges\Drivers\LaravelVersionDriver;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

beforeEach(function () {
    $this->filesystem = Mockery::mock(Filesystem::class);
    $this->filesystem->shouldReceive('isDirectory')->andReturn(true);
    $this->filesystem->shouldReceive('exists')->andReturn(true);
});

it('LaravelVersionDriver detects laravel version from composer.json', function ($composerData, $expectedVersion) {
    $this->filesystem->shouldReceive('get')->andReturn(json_encode($composerData));
    $projectContext = new ProjectContext($this->filesystem);

    $driver = new LaravelVersionDriver;
    $status = $driver->getStatus($projectContext, 'composer.json');

    expect($status)->toBe($expectedVersion);
})->with([
    [['require' => ['laravel/framework' => '^11.0']], '^11.0'],
    [['require' => ['illuminate/support' => '^10.0']], '^10.0'],
    [['require' => []], 'n/a'],
]);

it('LaravelVersionDriver returns correct color', function () {
    $this->filesystem->shouldReceive('get')->andReturn(json_encode([]));
    $projectContext = new ProjectContext($this->filesystem);
    $driver = new LaravelVersionDriver;
    expect($driver->getColor($projectContext, 'composer.json'))->toBe('ff2d20');
});

<?php

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use SchenkeIo\PackagingTools\Badges\Drivers\DownloadsDriver;
use SchenkeIo\PackagingTools\Badges\Drivers\GitHubTestDriver;
use SchenkeIo\PackagingTools\Badges\Drivers\ReleaseVersionDriver;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

beforeEach(function () {
    $this->filesystem = Mockery::mock(Filesystem::class);
    $this->filesystem->shouldReceive('isDirectory')->andReturn(true);
    $this->filesystem->shouldReceive('exists')->andReturn(true);
    $this->filesystem->shouldReceive('get')->andReturn(json_encode([
        'name' => 'vendor/package',
        'type' => 'library',
    ]));
    $this->projectContext = new ProjectContext($this->filesystem);
});

it('ReleaseVersionDriver fetches data from shields.io', function () {
    $httpMock = Mockery::mock(Factory::class);
    $httpMock->shouldReceive('get')->andReturn(new Response(
        new GuzzleResponse(200, [], json_encode([
            'message' => 'v1.2.3',
            'color' => 'blue',
        ]))
    ));
    Http::swap($httpMock);

    $driver = new ReleaseVersionDriver;
    $status = $driver->getStatus($this->projectContext, 'composer.json');
    $color = $driver->getColor($this->projectContext, 'composer.json');

    expect($status)->toBe('v1.2.3')
        ->and($color)->toBe('007ec6');
});

it('DownloadsDriver fetches data from shields.io', function () {
    $httpMock = Mockery::mock(Factory::class);
    $httpMock->shouldReceive('get')->andReturn(new Response(
        new GuzzleResponse(200, [], json_encode([
            'message' => '1.5k',
            'color' => 'brightgreen',
        ]))
    ));
    Http::swap($httpMock);

    $driver = new DownloadsDriver;
    $status = $driver->getStatus($this->projectContext, 'composer.json');
    $color = $driver->getColor($this->projectContext, 'composer.json');

    expect($status)->toBe('1.5k')
        ->and($color)->toBe('27AE60');
});

it('GitHubTestDriver fetches data from shields.io', function () {
    $httpMock = Mockery::mock(Factory::class);
    $httpMock->shouldReceive('get')->andReturn(new Response(
        new GuzzleResponse(200, [], json_encode([
            'message' => 'passing',
            'color' => 'brightgreen',
        ]))
    ));
    Http::swap($httpMock);

    $driver = new GitHubTestDriver;
    $status = $driver->getStatus($this->projectContext, 'tests.yml');
    $color = $driver->getColor($this->projectContext, 'tests.yml');

    expect($status)->toBe('passing')
        ->and($color)->toBe('27AE60');
});

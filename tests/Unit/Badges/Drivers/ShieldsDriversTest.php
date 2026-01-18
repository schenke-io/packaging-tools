<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Badges\Drivers\DownloadsDriver;
use SchenkeIo\PackagingTools\Badges\Drivers\GitHubTestDriver;
use SchenkeIo\PackagingTools\Badges\Drivers\LicenseDriver;
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

it('ReleaseVersionDriver returns n/a and grey', function () {
    $driver = new ReleaseVersionDriver;
    $status = $driver->getStatus($this->projectContext, 'composer.json');
    $color = $driver->getColor($this->projectContext, 'composer.json');

    expect($status)->toBe('n/a')
        ->and($color)->toBe('grey')
        ->and($driver->getSubject())->toBe('Version')
        ->and($driver->getUrl($this->projectContext, ''))->toContain('vendor/package')
        ->and($driver->getLinkUrl($this->projectContext, ''))->toContain('vendor/package')
        ->and($driver->detectPath($this->projectContext))->toBe('composer.json');
});

it('ReleaseVersionDriver returns null URL when unknown', function () {
    $this->projectContext->projectName = 'unknown';
    $driver = new ReleaseVersionDriver;
    expect($driver->getUrl($this->projectContext, ''))->toBeNull();
});

it('DownloadsDriver returns n/a and grey', function () {
    $driver = new DownloadsDriver;
    $status = $driver->getStatus($this->projectContext, 'composer.json');
    $color = $driver->getColor($this->projectContext, 'composer.json');

    expect($status)->toBe('n/a')
        ->and($color)->toBe('grey')
        ->and($driver->getSubject())->toBe('Downloads')
        ->and($driver->getUrl($this->projectContext, ''))->toContain('vendor/package')
        ->and($driver->getLinkUrl($this->projectContext, ''))->toContain('vendor/package')
        ->and($driver->detectPath($this->projectContext))->toBe('composer.json');
});

it('DownloadsDriver returns null URL when unknown', function () {
    $this->projectContext->projectName = 'unknown';
    $driver = new DownloadsDriver;
    expect($driver->getUrl($this->projectContext, ''))->toBeNull();
});

it('LicenseDriver returns n/a and grey', function () {
    $driver = new LicenseDriver;
    $status = $driver->getStatus($this->projectContext, 'composer.json');
    $color = $driver->getColor($this->projectContext, 'composer.json');

    expect($status)->toBe('n/a')
        ->and($color)->toBe('grey')
        ->and($driver->getSubject())->toBe('License')
        ->and($driver->getUrl($this->projectContext, ''))->toContain('vendor/package')
        ->and($driver->getLinkUrl($this->projectContext, ''))->toContain('vendor/package')
        ->and($driver->detectPath($this->projectContext))->toBe('composer.json');
});

it('LicenseDriver returns null URL when unknown', function () {
    $this->projectContext->repoOwner = 'unknown';
    $driver = new LicenseDriver;
    expect($driver->getUrl($this->projectContext, ''))->toBeNull()
        ->and($driver->getLinkUrl($this->projectContext, ''))->toBeNull();
});

it('GitHubTestDriver does not fetch data and returns defaults', function () {
    $driver = new GitHubTestDriver;
    $status = $driver->getStatus($this->projectContext, 'tests.yml');
    $color = $driver->getColor($this->projectContext, 'tests.yml');

    expect($status)->toBe('n/a')
        ->and($color)->toBe('grey')
        ->and($driver->getSubject())->toBe('Tests')
        ->and($driver->getUrl($this->projectContext, 'tests.yml'))->toContain('vendor/package')
        ->and($driver->getLinkUrl($this->projectContext, 'tests.yml'))->toContain('vendor/package');
});

it('GitHubTestDriver returns null when repo owner is unknown', function () {
    $this->projectContext->repoOwner = 'unknown';
    $driver = new GitHubTestDriver;
    expect($driver->getUrl($this->projectContext, 'tests.yml'))->toBeNull()
        ->and($driver->getLinkUrl($this->projectContext, 'tests.yml'))->toBeNull();
});

it('GitHubTestDriver detects path or returns null', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode([
        'name' => 'vendor/package',
        'type' => 'library',
    ]));
    $dirStatus = true;
    $filesystem->shouldReceive('isDirectory')->andReturnUsing(function () use (&$dirStatus) {
        return $dirStatus;
    });

    $projectContext = new ProjectContext($filesystem);
    $driver = new GitHubTestDriver;

    // missing directory
    $dirStatus = false;
    expect($driver->detectPath($projectContext))->toBeNull();

    // directory exists, no files
    $dirStatus = true;
    $filesystem->shouldReceive('files')->once()->andReturn([]);
    expect($driver->detectPath($projectContext))->toBeNull();

    // directory exists, with files
    $filesystem->shouldReceive('files')->once()->andReturn([
        'some-file.txt',
        'run-tests.yml',
    ]);
    expect($driver->detectPath($projectContext))->toBe('run-tests.yml');
});

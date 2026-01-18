<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Badges\Drivers\GitHubTestDriver;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

function createProjectContext($repoOwner = 'owner', $repoName = 'repo'): ProjectContext
{
    $filesystem = Mockery::mock(Filesystem::class);
    $projectRoot = '/root';
    $composerJsonPath = $projectRoot.'/composer.json';

    $filesystem->shouldReceive('isDirectory')->with($projectRoot)->andReturn(true);
    $filesystem->shouldReceive('exists')->with($composerJsonPath)->andReturn(true);
    $filesystem->shouldReceive('get')->with($composerJsonPath)->andReturn(json_encode([
        'name' => "$repoOwner/$repoName",
        'type' => 'library',
    ]));

    return new ProjectContext($filesystem, $projectRoot);
}

test('detectPath finds test workflow files', function () {
    $projectContext = createProjectContext();
    $driver = new GitHubTestDriver;

    $projectContext->filesystem->shouldReceive('fullPath')
        ->with('.github/workflows')
        ->andReturn('/root/.github/workflows');

    $projectContext->filesystem->shouldReceive('isDirectory')
        ->with('/root/.github/workflows')
        ->andReturn(true);

    $file1 = 'build.yml';
    $file2 = 'run-tests.yml';

    $projectContext->filesystem->shouldReceive('files')
        ->with('/root/.github/workflows')
        ->andReturn([$file1, $file2]);

    expect($driver->detectPath($projectContext))->toBe('run-tests.yml');
});

test('getUrl returns the GitHub actions badge URL', function () {
    $projectContext = createProjectContext('owner', 'repo');
    $driver = new GitHubTestDriver;

    $url = $driver->getUrl($projectContext, 'run-tests.yml');
    expect($url)->toBe('https://github.com/owner/repo/actions/workflows/run-tests.yml/badge.svg');
});

test('getLinkUrl returns the GitHub actions page URL', function () {
    $projectContext = createProjectContext('owner', 'repo');
    $driver = new GitHubTestDriver;

    $url = $driver->getLinkUrl($projectContext, 'run-tests.yml');
    expect($url)->toBe('https://github.com/owner/repo/actions/workflows/run-tests.yml');
});

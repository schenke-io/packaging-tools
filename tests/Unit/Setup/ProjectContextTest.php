<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('can initialize ProjectContext', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project', 'type' => 'library']));

    $context = new ProjectContext($filesystem);

    expect($context->projectName)->toBe('test/project')
        ->and($context->repoOwner)->toBe('test')
        ->and($context->repoName)->toBe('project')
        ->and($context->sourceRoot)->toBe('src');
});

it('extracts repository metadata', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'owner/repo']));

    $context = new ProjectContext($filesystem);

    expect($context->repoOwner)->toBe('owner')
        ->and($context->repoName)->toBe('repo');
});

it('handles unknown owner/repo', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'just-a-name']));

    $context = new ProjectContext($filesystem);

    expect($context->repoOwner)->toBe('unknown')
        ->and($context->repoName)->toBe('just-a-name');
});

it('fails if project root is not a directory', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(false);

    new ProjectContext($filesystem);
})->throws(Exception::class, 'Project root is not a directory');

it('fails if composer.json is missing', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(false);

    new ProjectContext($filesystem);
})->throws(Exception::class, 'composer.json not found');

it('fails if composer.json is invalid', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn('invalid json');

    new ProjectContext($filesystem);
})->throws(Exception::class, 'Invalid composer.json');

it('uses app as source root for project type', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project', 'type' => 'project']));

    $context = new ProjectContext($filesystem);

    expect($context->sourceRoot)->toBe('app');
});

it('can generate full paths', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn('{}');

    $context = new ProjectContext($filesystem);
    $path = $context->fullPath('some/file.php');

    expect($path)->toContain('some/file.php')
        ->and($path)->toBe(getcwd().'/some/file.php');
});

it('can get env values', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->with(Mockery::type('string'))->andReturnArg(0);
    $filesystem->shouldReceive('exists')->with(Mockery::subset([getcwd().'/.env']))->andReturn(true);
    // Overriding the previous generic mock if needed, but let's be more specific
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($path) => str_ends_with($path, 'composer.json')))->andReturn('{}');
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($path) => str_ends_with($path, '.env')))->andReturn("KEY1=VALUE1\nKEY2=\"VALUE2\"\nKEY3='VALUE3'");

    $context = new ProjectContext($filesystem);

    expect($context->getEnv('KEY1'))->toBe('VALUE1')
        ->and($context->getEnv('KEY2'))->toBe('VALUE2')
        ->and($context->getEnv('KEY3'))->toBe('VALUE3')
        ->and($context->getEnv('NON_EXISTENT', 'default'))->toBe('default');
});

it('can run a process', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn('{}');

    $context = new ProjectContext($filesystem);

    ob_start();
    $result = $context->runProcess('echo "hello"');
    $output = ob_get_clean();

    expect(trim($output))->toBe('hello')
        ->and($result)->toBeTrue();
});

it('returns false when a process fails', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn('{}');

    $context = new ProjectContext($filesystem);

    ob_start();
    $result = $context->runProcess('exit 1');
    ob_get_clean();

    expect($result)->toBeFalse();
});

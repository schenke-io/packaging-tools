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

it('handles comments and quotes in .env', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($path) => str_ends_with($path, 'composer.json')))->andReturn('{}');
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($path) => str_ends_with($path, '.env')))->andReturn(
        "KEY1=VALUE1 # comment\n".
        "KEY2=\"VALUE2 # with hash\" # comment\n".
        "KEY3='VALUE3 # with hash' # comment\n".
        "KEY4=\"VALUE4\"\n".
        'KEY5='
    );

    $context = new ProjectContext($filesystem);

    expect($context->getEnv('KEY1'))->toBe('VALUE1')
        ->and($context->getEnv('KEY2'))->toBe('VALUE2 # with hash')
        ->and($context->getEnv('KEY3'))->toBe('VALUE3 # with hash')
        ->and($context->getEnv('KEY4'))->toBe('VALUE4')
        ->and($context->getEnv('KEY5'))->toBe('');
});

it('handles edge cases in .env', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($path) => str_ends_with($path, 'composer.json')))->andReturn('{}');
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($path) => str_ends_with($path, '.env')))->andReturn(
        "KEY1=\"missing_end\n".
        "KEY2='missing_end\n".
        "KEY3=value #comment\n".
        "KEY4=  \n"
    );

    $context = new ProjectContext($filesystem);

    expect($context->getEnv('KEY1'))->toBe('"missing_end')
        ->and($context->getEnv('KEY2'))->toBe("'missing_end")
        ->and($context->getEnv('KEY3'))->toBe('value')
        ->and($context->getEnv('KEY4'))->toBe('');
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

it('can find model paths', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->with(getcwd())->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn('{}');

    $context = new ProjectContext($filesystem);

    // Mocking isDirectory for the getModelPath call - Case 1: workbench/app/Models exists
    $filesystem->shouldReceive('isDirectory')->with(Mockery::on(fn ($path) => str_contains($path, 'workbench/app/Models')))->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->with(Mockery::on(fn ($path) => str_contains($path, 'app/Models')))->andReturn(false);
    $filesystem->shouldReceive('isDirectory')->with(Mockery::on(fn ($path) => str_contains($path, 'src/Models')))->andReturn(false);

    expect($context->getModelPath())->toContain('workbench/app/Models');
});

it('finds app/Models if workbench/app/Models is missing', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->with(getcwd())->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn('{}');

    $context = new ProjectContext($filesystem);

    $filesystem->shouldReceive('isDirectory')->with(Mockery::on(fn ($path) => str_contains($path, 'workbench/app/Models')))->andReturn(false);
    $filesystem->shouldReceive('isDirectory')->with(Mockery::on(fn ($path) => str_contains($path, 'app/Models')))->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->with(Mockery::on(fn ($path) => str_contains($path, 'src/Models')))->andReturn(false);

    expect($context->getModelPath())->toContain('app/Models');
});

it('finds src/Models if workbench/app/Models and app/Models are missing', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->with(getcwd())->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn('{}');

    $context = new ProjectContext($filesystem);

    $filesystem->shouldReceive('isDirectory')->with(Mockery::on(fn ($path) => str_contains($path, 'workbench/app/Models')))->andReturn(false);
    $filesystem->shouldReceive('isDirectory')->with(Mockery::on(fn ($path) => str_contains($path, 'app/Models')))->andReturn(false);
    $filesystem->shouldReceive('isDirectory')->with(Mockery::on(fn ($path) => str_contains($path, 'src/Models')))->andReturn(true);

    expect($context->getModelPath())->toContain('src/Models');
});

it('throws exception if no model path exists', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->with(getcwd())->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn('{}');

    $context = new ProjectContext($filesystem);

    $filesystem->shouldReceive('isDirectory')->with(Mockery::on(fn ($path) => str_contains($path, 'Models')))->andReturn(false);

    $context->getModelPath();
})->throws(\SchenkeIo\PackagingTools\Exceptions\PackagingToolException::class);

it('detects laravel projects', function () {
    // case 1: type is project
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['type' => 'project']));
    $context = new ProjectContext($filesystem);
    expect($context->isLaravel())->toBeTrue();

    // case 2: laravel/framework is required
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['require' => ['laravel/framework' => '^11.0']]));
    $context = new ProjectContext($filesystem);
    expect($context->isLaravel())->toBeTrue();

    // case 3: neither
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode([]));
    $context = new ProjectContext($filesystem);
    expect($context->isLaravel())->toBeFalse();
});

it('detects workbench directory', function () {
    // case 1: workbench directory exists
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->with(getcwd())->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn('{}');
    $filesystem->shouldReceive('isDirectory')->with(Mockery::on(fn ($path) => str_contains($path, 'workbench')))->andReturn(true);

    $context = new ProjectContext($filesystem);
    expect($context->isWorkbench())->toBeTrue();

    // case 2: workbench directory does not exist
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->with(getcwd())->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn('{}');
    $filesystem->shouldReceive('isDirectory')->with(Mockery::on(fn ($path) => str_contains($path, 'workbench')))->andReturn(false);

    $context = new ProjectContext($filesystem);
    expect($context->isWorkbench())->toBeFalse();
});

it('returns correct migration path when workbench exists', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->with(getcwd())->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn('{}');

    $context = new ProjectContext($filesystem);

    $filesystem->shouldReceive('isDirectory')->with(Mockery::on(fn ($path) => str_contains($path, 'workbench')))->andReturn(true);
    expect($context->getMigrationPath())->toBe('workbench/database/migrations');
});

it('returns correct migration path when workbench is missing', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->with(getcwd())->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn('{}');

    $context = new ProjectContext($filesystem);

    $filesystem->shouldReceive('isDirectory')->with(Mockery::on(fn ($path) => str_contains($path, 'workbench')))->andReturn(false);
    expect($context->getMigrationPath())->toBe('database/migrations');
});

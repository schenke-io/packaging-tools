<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Definitions\AnalyseDefinition;
use SchenkeIo\PackagingTools\Setup\Definitions\CoverageDefinition;
use SchenkeIo\PackagingTools\Setup\Definitions\InfectionDefinition;
use SchenkeIo\PackagingTools\Setup\Definitions\MarkdownDefinition;
use SchenkeIo\PackagingTools\Setup\ProjectContext;
use SchenkeIo\PackagingTools\Setup\Requirements;
use SchenkeIo\PackagingTools\Setup\TaskRegistry;

it('has full defined tasks', function ($definition) {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'composer.json')) {
            return json_encode(['name' => 'test/project', 'type' => 'project']); // type=project -> sourceRoot=app
        }

        return 'analyse: true
coverage: true
test: pest
';
    });

    $projectContext = new ProjectContext($filesystem);
    $config = new Config($projectContext);

    expect($definition->explainConfig())->toBeString()
        ->and($definition->packages($config))->toBeInstanceOf(Requirements::class);

})->with(fn () => (new TaskRegistry)->getAllTasks());

it('AnalyseDefinition handles non-project source root', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project', 'type' => 'library']), 'analyse: true');

    $projectContext = new ProjectContext($filesystem);
    $config = new Config($projectContext);
    $definition = new AnalyseDefinition;

    expect($definition->packages($config)->data()['require-dev'])->toContain('phpstan/phpstan-phpunit');
});

it('CoverageDefinition handles pest', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']), 'coverage: true
test: pest');

    $projectContext = new ProjectContext($filesystem);
    $config = new Config($projectContext);
    $definition = new CoverageDefinition;

    expect($definition->commands($config))->toBe('vendor/bin/pest --coverage');
});

it('CoverageDefinition handles phpunit', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']), 'coverage: true
test: phpunit');

    $projectContext = new ProjectContext($filesystem);
    $config = new Config($projectContext);
    $definition = new CoverageDefinition;

    expect($definition->commands($config))->toBe('vendor/bin/phpunit --coverage');
});

it('InfectionDefinition handles enabled', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']), 'infection: true');

    $projectContext = new ProjectContext($filesystem);
    $config = new Config($projectContext);
    $definition = new InfectionDefinition;

    expect($definition->packages($config)->data()['require-dev'])->toContain('infection/infection')
        ->and($definition->commands($config))->toBe('./vendor/bin/infection');
});

it('MarkdownDefinition handles enabled', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']), 'markdown: "php make-md.php"');

    $projectContext = new ProjectContext($filesystem);
    $config = new Config($projectContext);
    $definition = new MarkdownDefinition;

    expect($definition->commands($config))->toBe('php make-md.php');
});

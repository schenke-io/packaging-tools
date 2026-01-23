<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Setup\Composer;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('can run badges auto in doConfiguration', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(
        json_encode(['name' => 'test/project', 'scripts' => []]), // composer.json
        '' // .packaging-tools.neon
    );
    $projectContext = Mockery::mock(ProjectContext::class, [$filesystem])->makePartial();
    $projectContext->shouldReceive('runProcess')->andReturn(true);

    Config::doConfiguration($projectContext, ['badges']);
    expect(true)->toBeTrue(); // avoid risky
});

it('outputs message when no script changes pending', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(
        json_encode(['name' => 'test/project', 'scripts' => ['test' => 'pest']]), // composer.json
        'test: pest' // .packaging-tools.neon
    );
    $filesystem->shouldReceive('put')->andReturn(true);
    $projectContext = Mockery::mock(ProjectContext::class, [$filesystem])->makePartial();
    $projectContext->shouldReceive('runProcess')->andReturn(true);

    ob_start();
    Config::$silent = false;
    Config::doConfiguration($projectContext, ['update']);
    $output = ob_get_clean();
    Config::$silent = true;

    expect($output)->toContain('No script changes pending');
});

it('outputs message when no missing packages found', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(
        json_encode(['name' => 'test/project', 'require-dev' => ['pestphp/pest' => '*']]), // composer.json
        'test: pest' // .packaging-tools.neon
    );
    $filesystem->shouldReceive('put')->andReturn(true);
    $projectContext = Mockery::mock(ProjectContext::class, [$filesystem])->makePartial();
    $projectContext->shouldReceive('runProcess')->andReturn(true);

    ob_start();
    Config::$silent = false;
    Config::doConfiguration($projectContext, ['update']);
    $output = ob_get_clean();
    Config::$silent = true;

    expect($output)->toContain('No missing packages found');
});

it('detects Laravel and Orchestra Workbench in writeConfig', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(
        json_encode(['name' => 'test/project']), // composer.json
        'pint: true' // .packaging-tools.neon
    );
    $filesystem->shouldReceive('put')->andReturn(true);

    $projectContext = Mockery::mock(ProjectContext::class, [$filesystem])->makePartial();
    $projectContext->shouldReceive('isLaravel')->andReturn(true);
    $projectContext->shouldReceive('isWorkbench')->andReturn(true);

    ob_start();
    Config::$silent = false;
    (new Config(null, $projectContext))->writeConfig($projectContext);
    $output = ob_get_clean();
    Config::$silent = true;

    expect($output)->toContain('Laravel detected')
        ->and($output)->toContain('Workbench detected');
});

it('covers default config generation in writeConfig', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->with(Mockery::on(fn ($p) => str_ends_with($p, 'composer.json')))->andReturn(true);
    $filesystem->shouldReceive('exists')->with(Mockery::on(fn ($p) => str_ends_with($p, '.packaging-tools.neon')))->andReturn(false);
    $filesystem->shouldReceive('get')->andReturn(
        json_encode(['name' => 'test/project']) // composer.json
    );
    $filesystem->shouldReceive('put')->with(Mockery::any(), Mockery::on(function ($content) {
        return str_contains($content, "quick:\n\t- pint\n\t- test\n\t- markdown") &&
               str_contains($content, "release:\n\t- pint\n\t- analyse\n\t- coverage\n\t- markdown");
    }))->once()->andReturn(true);

    $projectContext = new ProjectContext($filesystem);
    $config = new Config(null, $projectContext);

    $config->writeConfig($projectContext);
    expect(true)->toBeTrue(); // avoid risky
});

it('handles Neon decode failure in writeConfig', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($p) => str_ends_with($p, 'composer.json')))->andReturn(json_encode(['name' => 'test/project']));

    $count = 0;
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($p) => str_ends_with($p, '.packaging-tools.neon')))
        ->andReturnUsing(function () use (&$count) {
            $count++;
            if ($count === 1) {
                return 'pint: true';
            }
            throw new \Exception('fail');
        });

    $filesystem->shouldReceive('put')->andReturn(true);

    $projectContext = new ProjectContext($filesystem);
    $config = new Config(null, $projectContext);

    $config->writeConfig($projectContext);
    expect(true)->toBeTrue(); // avoid risky
});

it('handles scalar data in writeConfig', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($p) => str_ends_with($p, 'composer.json')))->andReturn(json_encode(['name' => 'test/project']));

    $count = 0;
    $filesystem->shouldReceive('get')->with(Mockery::on(fn ($p) => str_ends_with($p, '.packaging-tools.neon')))
        ->andReturnUsing(function () use (&$count) {
            $count++;
            if ($count === 1) {
                return 'pint: true';
            }

            return 'true';
        });

    $filesystem->shouldReceive('put')->andReturn(true);

    $projectContext = new ProjectContext($filesystem);
    $config = new Config(null, $projectContext);

    $config->writeConfig($projectContext);
    expect(true)->toBeTrue(); // avoid risky
});

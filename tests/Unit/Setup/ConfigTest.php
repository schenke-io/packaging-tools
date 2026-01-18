<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('Config throws exception on invalid neon', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']), 'invalid: neon: content:');
    $projectContext = new ProjectContext($filesystem);

    new Config(null, $projectContext);
})->throws(Exception::class, "Invalid configuration file '.packaging-tools.neon'");

it('Config throws exception on schema violation', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']), 'unknownKey: true');
    $projectContext = new ProjectContext($filesystem);

    new Config(null, $projectContext);
})->throws(Exception::class, "Configuration error in '.packaging-tools.neon'");

it('Config can handle custom tasks', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $neonContent = 'customTasks:
  my-task: SchenkeIo\PackagingTools\Tests\Unit\Setup\CustomTask';
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']), $neonContent);
    $projectContext = new ProjectContext($filesystem);

    $config = new Config(null, $projectContext);
    expect($config->config->customTasks)->toBe(['my-task' => 'SchenkeIo\PackagingTools\Tests\Unit\Setup\CustomTask']);
});

it('Config can do configuration', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(
        json_encode(['name' => 'test/project', 'scripts' => []]), // composer.json
        '' // .packaging-tools.neon
    );
    $filesystem->shouldReceive('put')->atLeast()->once();

    $oldArgv = $_SERVER['argv'];
    $_SERVER['argv'] = ['vendor/bin/packaging-tools', 'setup', 'update'];

    $projectContext = Mockery::mock(ProjectContext::class, [$filesystem])->makePartial();
    $projectContext->shouldReceive('runProcess');

    ob_start();
    Config::doConfiguration($projectContext);
    ob_get_clean();

    $_SERVER['argv'] = $oldArgv;
    expect($projectContext->projectName)->toBe('test/project');
});

it('Config can show help for unknown parameter', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(
        json_encode(['name' => 'test/project', 'scripts' => []]), // composer.json
        '' // .packaging-tools.neon
    );

    $oldArgv = $_SERVER['argv'];
    $_SERVER['argv'] = ['vendor/bin/packaging-tools', 'setup', 'foo'];

    $projectContext = new ProjectContext($filesystem);

    ob_start();
    Config::$silent = false;
    Config::doConfiguration($projectContext);
    Config::$silent = true;
    $output = ob_get_clean();

    $_SERVER['argv'] = $oldArgv;
    expect($output)->toContain("unknown parameter 'foo'");
});

it('Config writeConfig skips if file exists and no data', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(
        json_encode(['name' => 'test/project', 'scripts' => []]), // composer.json
        '' // .packaging-tools.neon
    );
    // expect NO put for composer.json AND NO put for the neon config
    $filesystem->shouldNotReceive('put');

    $oldArgv = $_SERVER['argv'];
    $_SERVER['argv'] = ['vendor/bin/packaging-tools', 'setup', 'config'];

    $projectContext = new ProjectContext($filesystem);

    ob_start();
    Config::$silent = false;
    Config::doConfiguration($projectContext);
    Config::$silent = true;
    $output = ob_get_clean();

    $_SERVER['argv'] = $oldArgv;
    expect($output)->toContain('config file .packaging-tools.neon is already up to date.');
});

class CustomTask extends \SchenkeIo\PackagingTools\Setup\Definitions\BaseDefinition
{
    public function schema(): \Nette\Schema\Schema
    {
        return \Nette\Schema\Expect::string();
    }

    public function explainConfig(): string
    {
        return '';
    }

    protected function getPackages(Config $config): \SchenkeIo\PackagingTools\Setup\Requirements
    {
        return new \SchenkeIo\PackagingTools\Setup\Requirements;
    }

    protected function getCommands(Config $config): string|array
    {
        return '';
    }

    public function explainTask(): string
    {
        return '';
    }
}

it('can detect c2p deltas', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'composer.json')) {
            return json_encode([
                'require-dev' => [
                    'pestphp/pest' => '^3.0',
                    'laravel/pint' => '^1.0',
                ],
            ]);
        }
        if (str_ends_with($path, '.packaging-tools.neon')) {
            return 'pint: false';
        }

        return '';
    });
    $projectContext = new ProjectContext($filesystem);

    $config = new Config(null, $projectContext);
    $deltas = $config->getC2pDeltas();

    expect($deltas)->toHaveKey('test', 'pest')
        ->and($deltas)->toHaveKey('pint', true);
});

it('merges deltas in writeConfig', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'composer.json')) {
            return json_encode([
                'require-dev' => [
                    'pestphp/pest' => '^3.0',
                ],
            ]);
        }
        if (str_ends_with($path, '.packaging-tools.neon')) {
            return 'pint: true';
        }

        return '';
    });
    $filesystem->shouldReceive('put')->withArgs(function ($path, $content) {
        if (str_ends_with($path, '.packaging-tools.neon')) {
            return str_contains($content, 'test: pest') && str_contains($content, 'pint: true');
        }

        return true;
    })->once();

    $projectContext = new ProjectContext($filesystem);
    $config = new Config(null, $projectContext);

    ob_start();
    $config->writeConfig($projectContext);
    ob_get_clean();
});

<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Exceptions\PackagingToolException;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

beforeEach(function () {
    $this->filesystem = Mockery::mock(Filesystem::class);
    $this->filesystem->shouldReceive('isDirectory')->andReturn(true);
    $this->filesystem->shouldReceive('exists')->with(Mockery::on(fn ($path) => str_ends_with($path, 'composer.json')))->andReturn(true);
    $this->filesystem->shouldReceive('get')->with(Mockery::on(fn ($path) => str_ends_with($path, 'composer.json')))->andReturn(json_encode(['name' => 'test/project']));
    $this->projectContext = new ProjectContext($this->filesystem);
});

it('throws exception on invalid neon file in constructor', function () {
    $this->filesystem->shouldReceive('exists')->with(Mockery::on(fn ($path) => str_ends_with($path, '.packaging-tools.neon')))->andReturn(true);
    $this->filesystem->shouldReceive('get')->with(Mockery::on(fn ($path) => str_ends_with($path, '.packaging-tools.neon')))->andReturn('invalid: neon: [');

    expect(fn () => new Config(null, $this->projectContext))->toThrow(PackagingToolException::class);
});

it('handles exception in getC2pDeltas', function () {
    $this->filesystem->shouldReceive('exists')->with(Mockery::on(fn ($path) => str_ends_with($path, '.packaging-tools.neon')))->andReturn(true);
    // successfully construct first
    $this->filesystem->shouldReceive('get')->with(Mockery::on(fn ($path) => str_ends_with($path, '.packaging-tools.neon')))->once()->andReturn("test: 'pest'");
    $config = new Config(null, $this->projectContext);

    // Now make it fail for getC2pDeltas
    $this->filesystem->shouldReceive('get')->with(Mockery::on(fn ($path) => str_ends_with($path, '.packaging-tools.neon')))->andThrow(new \Exception('error'));

    expect($config->getC2pDeltas())->toBeArray();
});

it('outputs error when writing config fails', function () {
    $this->filesystem->shouldReceive('exists')->with(Mockery::on(fn ($path) => str_ends_with($path, '.packaging-tools.neon')))->andReturn(false);
    $this->filesystem->shouldReceive('put')->andReturn(false);

    Config::$silent = false;
    ob_start();
    $config = new Config(['test' => 'pest'], $this->projectContext);
    $config->writeConfig($this->projectContext);
    $output = ob_get_clean();
    Config::$silent = true;

    expect($output)->toContain('error writing');
});

it('outputs error when composer update fails', function () {
    $this->filesystem->shouldReceive('exists')->with(Mockery::on(fn ($path) => str_ends_with($path, '.packaging-tools.neon')))->andReturn(true);
    $this->filesystem->shouldReceive('get')->with(Mockery::on(fn ($path) => str_ends_with($path, '.packaging-tools.neon')))->andReturn("pint: true\ntest: ''");
    $this->filesystem->shouldReceive('put')->andReturn(true);

    $mockContext = Mockery::mock(ProjectContext::class);
    $mockContext->projectRoot = $this->projectContext->projectRoot;
    $mockContext->filesystem = $this->filesystem;
    $mockContext->composerJsonPath = $this->projectContext->composerJsonPath;
    $mockContext->composerJsonContent = $this->projectContext->composerJsonContent;
    $mockContext->composerJson = ['name' => 'test/project'];
    $mockContext->shouldReceive('fullPath')->andReturnUsing(fn ($p) => $this->projectContext->fullPath($p));
    $mockContext->shouldReceive('isLaravel')->andReturn(false);
    $mockContext->shouldReceive('isOrchestraWorkbench')->andReturn(false);

    $mockContext->shouldReceive('runProcess')
        ->with('composer require --dev laravel/pint')
        ->once()
        ->andReturn(false);

    Config::$silent = false;
    ob_start();
    Config::doConfiguration($mockContext, ['update']);
    $output = ob_get_clean();
    Config::$silent = true;

    expect($output)->toContain('command failed');
});

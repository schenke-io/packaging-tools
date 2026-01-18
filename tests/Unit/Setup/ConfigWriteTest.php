<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

beforeEach(function () {
    $this->filesystem = Mockery::mock(Filesystem::class);
    $this->filesystem->shouldReceive('isDirectory')->andReturn(true);
    $this->filesystem->shouldReceive('exists')->with(Mockery::on(fn ($path) => str_ends_with($path, 'composer.json')))->andReturn(true);
    $this->filesystem->shouldReceive('get')->with(Mockery::on(fn ($path) => str_ends_with($path, 'composer.json')))->andReturn(json_encode(['name' => 'test/project']));
    $this->projectContext = new ProjectContext($this->filesystem);
});

it('handles Neon::decode exception in writeConfig', function () {
    $configPath = $this->projectContext->fullPath('.packaging-tools.neon');
    $parentPath = dirname($this->projectContext->projectRoot).'/.packaging-tools.neon';
    // First call to exists() is in constructor, returns false
    // Second call to exists() is in constructor (parent dir), returns false
    // Third call to exists() is in writeConfig, returns true
    $this->filesystem->shouldReceive('exists')->with($configPath)->andReturn(false, true);
    $this->filesystem->shouldReceive('exists')->with($parentPath)->andReturn(false);

    // Config constructor with empty data and no file
    $config = new Config(null, $this->projectContext);

    // Now make the file exist but be invalid for writeConfig
    $this->filesystem->shouldReceive('get')->with($configPath)->andReturn('invalid neon: [');
    $this->filesystem->shouldReceive('put')->andReturn(true);

    // We expect it to not crash because of the try-catch
    $config->writeConfig($this->projectContext);
    expect(true)->toBeTrue();
});

it('handles scalar data after decoding in writeConfig', function () {
    $configPath = $this->projectContext->fullPath('.packaging-tools.neon');
    $parentPath = dirname($this->projectContext->projectRoot).'/.packaging-tools.neon';
    // First call to exists() is in constructor, returns false
    // Second call to exists() is in constructor (parent dir), returns false
    // Third call to exists() is in writeConfig, returns true
    $this->filesystem->shouldReceive('exists')->with($configPath)->andReturn(false, true);
    $this->filesystem->shouldReceive('exists')->with($parentPath)->andReturn(false);

    // Config constructor with empty data and no file
    $config = new Config(null, $this->projectContext);

    // Now make the file exist but return a scalar
    $this->filesystem->shouldReceive('get')->with($configPath)->andReturn('some scalar');
    $this->filesystem->shouldReceive('put')->andReturn(true);

    $config->writeConfig($this->projectContext);
    expect(true)->toBeTrue();
});

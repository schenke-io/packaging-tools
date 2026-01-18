<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Markdown\ClassReader;
use SchenkeIo\PackagingTools\Markdown\Providers\ReflectionProvider;
use SchenkeIo\PackagingTools\Setup\ProjectContext;
use SchenkeIo\PackagingTools\Tests\Unit\Markdown\SampleClass;

it('ReflectionProvider returns class markdown', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $projectContext = new ProjectContext($filesystem);

    $provider = new ReflectionProvider(SampleClass::class);
    $content = $provider->getContent($projectContext, 'tests/Data');

    expect($content)->toContain('SampleClass')
        ->and($content)->toContain('Class for testing')
        ->and($content)->toContain('sampleMethod');
});

it('ClassReader can extract class data', function () {
    $reader = ClassReader::fromClass(SampleClass::class);
    $data = $reader->getClassDataFromClass(SampleClass::class);

    expect($data['short'])->toBe('SampleClass')
        ->and($data['summary'])->toBe('Class for testing')
        ->and($data['methods'])->toHaveKey('sampleMethod');
});

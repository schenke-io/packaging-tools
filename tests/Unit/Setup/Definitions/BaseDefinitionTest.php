<?php

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Definitions\BaseDefinition;
use SchenkeIo\PackagingTools\Setup\ProjectContext;
use SchenkeIo\PackagingTools\Setup\Requirements;

it('uses custom task name from constructor', function () {
    $def = new class('custom') extends BaseDefinition
    {
        public function schema(): Schema
        {
            return Expect::bool();
        }

        public function explainConfig(): string
        {
            return '';
        }

        public function explainTask(): string
        {
            return '';
        }
    };
    // use reflection to check protected property
    $ref = new ReflectionClass($def);
    $prop = $ref->getProperty('taskName');
    $prop->setAccessible(true);
    expect($prop->getValue($def))->toBe('custom');
});

class MyExampleTaskDefinition extends BaseDefinition
{
    public function schema(): Schema
    {
        return Expect::bool();
    }

    public function explainConfig(): string
    {
        return '';
    }

    public function explainTask(): string
    {
        return '';
    }
}

it('derives kebab-case task name from a named class', function () {
    $def = new MyExampleTaskDefinition;
    $ref = new ReflectionClass($def);
    $prop = $ref->getProperty('taskName');
    $prop->setAccessible(true);
    expect($prop->getValue($def))->toBe('my-example-task');
});

it('enables group task automatically', function () {
    $def = new class('group') extends BaseDefinition
    {
        public function schema(): Schema
        {
            return Expect::bool();
        }

        public function explainConfig(): string
        {
            return '';
        }

        public function explainTask(): string
        {
            return '';
        }
    };
    $mockContext = Mockery::mock(ProjectContext::class);
    $mockContext->projectRoot = '/root';
    $mockContext->shouldReceive('fullPath')->andReturnUsing(fn ($p) => "/root/$p");
    $filesystem = Mockery::mock(\Illuminate\Filesystem\Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(false);
    $mockContext->filesystem = $filesystem;
    $mockContext->composerJson = [];
    $mockContext->composerJsonContent = '{}';

    $config = new Config([], $mockContext);

    expect($def->commands($config))->toBe([]);
});

it('returns empty packages and commands by default', function () {
    $def = new class('analyse') extends BaseDefinition
    {
        public function schema(): Schema
        {
            return Expect::bool();
        }

        public function explainConfig(): string
        {
            return '';
        }

        public function explainTask(): string
        {
            return '';
        }
    };
    $mockContext = Mockery::mock(ProjectContext::class);
    $mockContext->projectRoot = '/root';
    $mockContext->shouldReceive('fullPath')->andReturnUsing(fn ($p) => "/root/$p");
    $filesystem = Mockery::mock(\Illuminate\Filesystem\Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(false);
    $mockContext->filesystem = $filesystem;
    $mockContext->composerJson = [];
    $mockContext->composerJsonContent = '{}';

    $config = new Config(['analyse' => true], $mockContext);

    expect($def->packages($config))->toBeInstanceOf(Requirements::class)
        ->and($def->commands($config))->toBe([]);
});

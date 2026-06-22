<?php

namespace SchenkeIo\PackagingTools\Tests\Unit\Traits;

pest()->group('unit');

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Mockery as m;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\ProjectContext;
use SchenkeIo\PackagingTools\Traits\GeneratesPackageMigrations;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;

class MockCommand extends Command
{
    use GeneratesPackageMigrations;

    public function handle() {}

    public function isMigrationsGeneratorInstalled(): bool
    {
        return $this->traitIsMigrationsGeneratorInstalled();
    }

    protected function traitIsMigrationsGeneratorInstalled(): bool
    {
        // call the trait method directly using reflection to avoid recursion
        $instance = new class
        {
            use GeneratesPackageMigrations;
        };
        $reflection = new \ReflectionObject($instance);
        $method = $reflection->getMethod('isMigrationsGeneratorInstalled');
        $method->setAccessible(true);

        return $method->invoke($instance);
    }
}

class MockModel extends Model
{
    protected $table = 'mock_table';
}

afterEach(function () {
    m::close();
    Facade::clearResolvedInstances();
});

it('does nothing if not a command context', function () {
    $obj = new class
    {
        use GeneratesPackageMigrations;
    };
    $obj->generatePackageMigrations();
    expect(true)->toBeTrue();
});

function setupMockFilesystem()
{
    $filesystem = m::mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true)->byDefault();
    $filesystem->shouldReceive('get')->with(m::type('string'))->andReturnUsing(function ($path) {
        if (str_contains($path, 'composer.json')) {
            return json_encode(['name' => 'test/pkg']);
        }

        return '';
    })->byDefault();
    $filesystem->shouldReceive('isDirectory')->andReturn(true)->byDefault();
    $filesystem->shouldReceive('allFiles')->withAnyArgs()->andReturn([])->byDefault();
    $filesystem->shouldReceive('cleanDirectory')->withAnyArgs()->andReturnTrue()->byDefault();
    $filesystem->shouldReceive('chmod')->withAnyArgs()->andReturnTrue()->byDefault();

    return $filesystem;
}

it('generates migrations with connection and tables from config', function () {
    $filesystem = setupMockFilesystem();
    $filesystem->shouldReceive('isDirectory')->andReturnUsing(function ($path) {
        return ! str_contains($path, 'workbench');
    });

    $projectContext = new ProjectContext($filesystem);
    $config = new Config(['migrations' => 'mysql:table1,table2'], $projectContext);

    $command = m::mock(MockCommand::class)->makePartial();
    $command->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('traitIsMigrationsGeneratorInstalled')->andReturnTrue();
    $ref = new \ReflectionProperty(Command::class, 'output');
    $ref->setAccessible(true);
    $ref->setValue($command, m::mock(OutputInterface::class));

    $command->shouldReceive('call')->with('migrate:generate', m::type('array'))->once()->andReturnUsing(function ($commandName, $args) {
        expect(str_contains($args['--path'], 'database/migrations'))->toBeTrue();
        expect(str_contains($args['--tables'], 'table1,table2'))->toBeTrue();
        expect($args['--connection'] ?? '')->toBe('mysql');

        return 0;
    });

    $command->generatePackageMigrations($projectContext, $config);
});

it('generates migrations with connection only and uses model discovery', function () {
    $filesystem = setupMockFilesystem();
    $filesystem->shouldReceive('isDirectory')->andReturnUsing(function ($path) {
        return ! str_contains($path, 'workbench');
    });

    $projectContext = new ProjectContext($filesystem);
    $config = new Config(['migrations' => 'sqlite'], $projectContext);

    $command = m::mock(MockCommand::class)->makePartial();
    $command->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('traitIsMigrationsGeneratorInstalled')->andReturnTrue();
    $ref = new \ReflectionProperty(Command::class, 'output');
    $ref->setAccessible(true);
    $ref->setValue($command, m::mock(OutputInterface::class));

    // Mock model discovery
    $file = m::mock(SplFileInfo::class);
    $file->shouldReceive('getExtension')->andReturn('php');
    $file->shouldReceive('getRealPath')->andReturn($projectContext->fullPath('app/Models/MockModel.php'));

    $filesystem->shouldReceive('allFiles')->with($projectContext->fullPath('app/Models'))->andReturn([$file]);
    $filesystem->shouldReceive('get')->with($projectContext->fullPath('app/Models/MockModel.php'))->andReturn('<?php namespace SchenkeIo\PackagingTools\Tests\Unit\Traits; class MockModel extends \Illuminate\Database\Eloquent\Model { protected $table = "mock_table"; }');

    $command->shouldReceive('call')->with('migrate:generate', m::type('array'))->once()->andReturnUsing(function ($commandName, $args) {
        expect(str_contains($args['--tables'], 'mock_table'))->toBeTrue();
        expect($args['--connection'] ?? '')->toBe('sqlite');

        return 0;
    });

    $command->generatePackageMigrations($projectContext, $config);
});

it('generates migrations with connection:* and uses model discovery', function () {
    $filesystem = setupMockFilesystem();
    $filesystem->shouldReceive('isDirectory')->andReturnUsing(function ($path) {
        return ! str_contains($path, 'workbench');
    });

    $projectContext = new ProjectContext($filesystem);
    $config = new Config(['migrations' => 'mysql:*'], $projectContext);

    $command = m::mock(MockCommand::class)->makePartial();
    $command->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('traitIsMigrationsGeneratorInstalled')->andReturnTrue();
    $ref = new \ReflectionProperty(Command::class, 'output');
    $ref->setAccessible(true);
    $ref->setValue($command, m::mock(OutputInterface::class));

    // Mock model discovery
    $file = m::mock(SplFileInfo::class);
    $file->shouldReceive('getExtension')->andReturn('php');
    $file->shouldReceive('getRealPath')->andReturn($projectContext->fullPath('app/Models/MockModel.php'));

    $filesystem->shouldReceive('allFiles')->with($projectContext->fullPath('app/Models'))->andReturn([$file]);
    $filesystem->shouldReceive('get')->with($projectContext->fullPath('app/Models/MockModel.php'))->andReturn('<?php namespace SchenkeIo\PackagingTools\Tests\Unit\Traits; class MockModel extends \Illuminate\Database\Eloquent\Model { protected $table = "mock_table"; }');

    $command->shouldReceive('call')->with('migrate:generate', m::type('array'))->once()->andReturnUsing(function ($commandName, $args) {
        expect(str_contains($args['--tables'], 'mock_table'))->toBeTrue();
        expect($args['--connection'] ?? '')->toBe('mysql');

        return 0;
    });

    $command->generatePackageMigrations($projectContext, $config);
});

it('handles null config by using model discovery', function () {
    $filesystem = setupMockFilesystem();

    $projectContext = new ProjectContext($filesystem);
    $config = new Config(['migrations' => null], $projectContext);

    $command = m::mock(MockCommand::class)->makePartial();
    $command->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('traitIsMigrationsGeneratorInstalled')->andReturnTrue();
    $ref = new \ReflectionProperty(Command::class, 'output');
    $ref->setAccessible(true);
    $ref->setValue($command, m::mock(OutputInterface::class));

    $command->shouldReceive('call')->with('migrate:generate', m::type('array'))->once()->andReturnUsing(function ($commandName, $args) {
        expect($args['--connection'] ?? null)->toBeNull();
        expect($args['--tables'])->toBe('batches,cache,cache_locks,failed_jobs,job_batches,jobs,migrations,password_reset_tokens,sessions');

        return 0;
    });

    $command->generatePackageMigrations($projectContext, $config);
});

it('outputs error if migrations generator is NOT installed', function () {
    $command = m::mock(MockCommand::class)->makePartial();
    $command->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('traitIsMigrationsGeneratorInstalled')->andReturn(false);

    $command->shouldReceive('error')->with('Package kitloong/laravel-migrations-generator is NOT installed.')->once();
    $command->shouldReceive('info')->with('Please run: composer require --dev kitloong/laravel-migrations-generator')->once();

    $command->generatePackageMigrations();
});

it('tests isMigrationsGeneratorInstalled directly', function () {
    $command = new MockCommand;
    // In our test environment, it should be true because it is in require-dev
    expect($command->isMigrationsGeneratorInstalled())->toBeTrue();
});

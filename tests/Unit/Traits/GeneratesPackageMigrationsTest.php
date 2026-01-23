<?php

namespace SchenkeIo\PackagingTools\Tests\Unit\Traits;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\File;
use Mockery as m;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\ProjectContext;
use SchenkeIo\PackagingTools\Traits\GeneratesPackageMigrations;

class MockCommand extends Command
{
    use GeneratesPackageMigrations;

    public function handle() {}
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

it('generates migrations with connection and tables from config', function () {
    $filesystem = m::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->with(m::on(fn ($path) => str_contains($path, 'workbench')))->andReturn(false);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/pkg']));

    $projectContext = new ProjectContext($filesystem);
    $config = new Config(['migrations' => 'mysql:table1,table2'], $projectContext);

    $command = m::mock(MockCommand::class)->makePartial();
    $ref = new \ReflectionProperty(Command::class, 'output');
    $ref->setAccessible(true);
    $ref->setValue($command, m::mock(\Symfony\Component\Console\Output\OutputInterface::class));

    $command->shouldReceive('call')->with('migrate:generate', m::on(function ($args) {
        return str_contains($args['--path'], 'database/migrations') &&
               str_contains($args['--tables'], 'table1,table2') &&
               $args['--connection'] === 'mysql';
    }))->once();

    File::shouldReceive('isDirectory')->andReturn(false); // for MigrationCleaner
    File::shouldReceive('cleanDirectory')->andReturnTrue();

    $command->generatePackageMigrations($projectContext, $config);
});

it('generates migrations with connection only and uses model discovery', function () {
    $filesystem = m::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/pkg']));

    $projectContext = new ProjectContext($filesystem);
    $config = new Config(['migrations' => 'sqlite'], $projectContext);

    $command = m::mock(MockCommand::class)->makePartial();
    $ref = new \ReflectionProperty(Command::class, 'output');
    $ref->setAccessible(true);
    $ref->setValue($command, m::mock(\Symfony\Component\Console\Output\OutputInterface::class));

    // Mock model discovery
    $file = m::mock(\Symfony\Component\Finder\SplFileInfo::class);
    $file->shouldReceive('getExtension')->andReturn('php');
    $file->shouldReceive('getRealPath')->andReturn('/path/to/models/MockModel.php');

    // override getModelPath behavior for this test
    $projectContext = m::mock(ProjectContext::class);
    $projectContext->shouldReceive('getModelPath')->andReturn('/path/to/models');
    $projectContext->shouldReceive('fullPath')->andReturn('/path/to/migrations');
    $projectContext->shouldReceive('getMigrationPath')->andReturn('database/migrations');

    File::shouldReceive('isDirectory')->with('/path/to/models')->andReturn(true);
    File::shouldReceive('allFiles')->with('/path/to/models')->andReturn([$file]);
    File::shouldReceive('get')->with('/path/to/models/MockModel.php')->andReturn('<?php namespace SchenkeIo\PackagingTools\Tests\Unit\Traits; class MockModel extends \Illuminate\Database\Eloquent\Model { protected $table = "mock_table"; }');
    File::shouldReceive('isDirectory')->with('/path/to/migrations')->andReturn(true);
    File::shouldReceive('cleanDirectory')->with('/path/to/migrations')->andReturnTrue();
    File::shouldReceive('allFiles')->with('/path/to/migrations')->andReturn([]);

    // for MigrationCleaner
    File::shouldReceive('isDirectory')->andReturn(false);

    $command->shouldReceive('call')->with('migrate:generate', m::on(function ($args) {
        return str_contains($args['--tables'], 'mock_table') && $args['--connection'] === 'sqlite';
    }))->once();

    $command->generatePackageMigrations($projectContext, $config);
});

it('generates migrations with connection:* and uses model discovery', function () {
    $filesystem = m::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/pkg']));

    $projectContext = new ProjectContext($filesystem);
    $config = new Config(['migrations' => 'mysql:*'], $projectContext);

    $command = m::mock(MockCommand::class)->makePartial();
    $ref = new \ReflectionProperty(Command::class, 'output');
    $ref->setAccessible(true);
    $ref->setValue($command, m::mock(\Symfony\Component\Console\Output\OutputInterface::class));

    // Mock model discovery
    $file = m::mock(\Symfony\Component\Finder\SplFileInfo::class);
    $file->shouldReceive('getExtension')->andReturn('php');
    $file->shouldReceive('getRealPath')->andReturn('/path/to/models/MockModel.php');

    // override getModelPath behavior for this test
    $projectContext = m::mock(ProjectContext::class);
    $projectContext->shouldReceive('getModelPath')->andReturn('/path/to/models');
    $projectContext->shouldReceive('fullPath')->andReturn('/path/to/migrations');
    $projectContext->shouldReceive('getMigrationPath')->andReturn('database/migrations');

    File::shouldReceive('isDirectory')->with('/path/to/models')->andReturn(true);
    File::shouldReceive('allFiles')->with('/path/to/models')->andReturn([$file]);
    File::shouldReceive('get')->with('/path/to/models/MockModel.php')->andReturn('<?php namespace SchenkeIo\PackagingTools\Tests\Unit\Traits; class MockModel extends \Illuminate\Database\Eloquent\Model { protected $table = "mock_table"; }');
    File::shouldReceive('isDirectory')->with('/path/to/migrations')->andReturn(true);
    File::shouldReceive('cleanDirectory')->with('/path/to/migrations')->andReturnTrue();
    File::shouldReceive('allFiles')->with('/path/to/migrations')->andReturn([]);

    // for MigrationCleaner
    File::shouldReceive('isDirectory')->andReturn(false);

    $command->shouldReceive('call')->with('migrate:generate', m::on(function ($args) {
        return str_contains($args['--tables'], 'mock_table') && $args['--connection'] === 'mysql';
    }))->once();

    $command->generatePackageMigrations($projectContext, $config);
});

it('handles null config by using model discovery', function () {
    $filesystem = m::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/pkg']));

    $projectContext = new ProjectContext($filesystem);
    $config = new Config(['migrations' => null], $projectContext);

    $command = m::mock(MockCommand::class)->makePartial();
    $ref = new \ReflectionProperty(Command::class, 'output');
    $ref->setAccessible(true);
    $ref->setValue($command, m::mock(\Symfony\Component\Console\Output\OutputInterface::class));

    // for MigrationCleaner
    File::shouldReceive('isDirectory')->andReturn(false);
    File::shouldReceive('allFiles')->andReturn([]);
    File::shouldReceive('cleanDirectory')->andReturnTrue();

    $command->shouldReceive('call')->with('migrate:generate', m::on(function ($args) {
        return ! isset($args['--connection']) && $args['--tables'] === 'batches,cache,cache_locks,failed_jobs,job_batches,jobs,migrations,password_reset_tokens,sessions';
    }))->once();

    $command->generatePackageMigrations($projectContext, $config);
});

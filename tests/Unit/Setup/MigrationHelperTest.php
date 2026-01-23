<?php

namespace SchenkeIo\PackagingTools\Tests\Unit\Setup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Mockery;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\MigrationHelper;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

afterEach(function () {
    File::clearResolvedInstances();
});

function getProjectContext(): ProjectContext
{
    $fs = Mockery::mock(Filesystem::class);
    $fs->shouldReceive('isDirectory')->with('/root')->andReturn(true);
    $fs->shouldReceive('isDirectory')->andReturn(false);
    $fs->shouldReceive('exists')->andReturn(true);
    $fs->shouldReceive('get')->andReturn(json_encode([
        'name' => 'vendor/package',
        'type' => 'library',
    ]));

    return new ProjectContext($fs, '/root');
}

test('resolveMigrationTargets with * after colon', function () {
    $fs = Mockery::mock(Filesystem::class);
    $fs->shouldReceive('isDirectory')->with('/root')->andReturn(true);
    $fs->shouldReceive('isDirectory')->with('/root/src/Models')->andReturn(true);
    $fs->shouldReceive('isDirectory')->andReturn(false);
    $fs->shouldReceive('exists')->andReturn(true);
    $fs->shouldReceive('get')->andReturn(json_encode([
        'name' => 'vendor/package',
        'type' => 'library',
    ]));

    $projectContext = new ProjectContext($fs, '/root');
    $modelPath = '/root/src/Models';

    $file = Mockery::mock();
    $file->shouldReceive('getExtension')->andReturn('php');
    $file->shouldReceive('getRealPath')->andReturn($modelPath.'/GoodModel.php');

    File::shouldReceive('isDirectory')->with($modelPath)->andReturn(true);
    File::shouldReceive('allFiles')->with($modelPath)->andReturn([$file]);

    if (! class_exists('App\Models\GoodModel')) {
        eval('namespace App\Models; use Illuminate\Database\Eloquent\Model; class GoodModel extends Model { protected $table = "good_table"; }');
    }
    File::shouldReceive('get')->with($modelPath.'/GoodModel.php')->andReturn('<?php namespace App\Models; class GoodModel extends Model {}');

    $config = new Config(['migrations' => 'mysql:*'], $projectContext);

    $resolved = MigrationHelper::resolveMigrationTargets($config, $projectContext);

    expect($resolved['connection'])->toBe('mysql');
    expect($resolved['tables'])->toContain('good_table');
    expect($resolved['tables'])->toContain('migrations');
});

test('resolveMigrationTargets with empty table list after colon', function () {
    $projectContext = getProjectContext();

    $config = new Config(['migrations' => 'mysql:'], $projectContext);

    $resolved = MigrationHelper::resolveMigrationTargets($config, $projectContext);

    expect($resolved['connection'])->toBe('mysql');
    // should only contain system tables, not model tables
    expect($resolved['tables'])->toEqualCanonicalizing(MigrationHelper::$systemTables);
});

test('getTablesFromModels ignores classes that throw exceptions', function () {
    $projectContext = Mockery::mock(ProjectContext::class);
    $modelPath = '/root/app/Models';
    $projectContext->shouldReceive('getModelPath')->andReturn($modelPath);

    $file = Mockery::mock();
    $file->shouldReceive('getExtension')->andReturn('php');
    $file->shouldReceive('getRealPath')->andReturn($modelPath.'/NotAModel.php');

    File::shouldReceive('isDirectory')->with($modelPath)->andReturn(true);
    File::shouldReceive('allFiles')->with($modelPath)->andReturn([$file]);
    File::shouldReceive('get')->with($modelPath.'/NotAModel.php')->andReturn('<?php namespace App\Models; class NotAModel {}');

    // Let's define a class that exists but is not a model
    if (! class_exists('App\Models\NotAModel')) {
        eval('namespace App\Models; class NotAModel {}');
    }

    $tables = MigrationHelper::getTablesFromModels($projectContext);
    expect($tables)->toBeEmpty();
});

test('getTablesFromModels with a valid model', function () {
    $projectContext = Mockery::mock(ProjectContext::class);
    $modelPath = '/root/app/Models';
    $projectContext->shouldReceive('getModelPath')->andReturn($modelPath);

    $file = Mockery::mock();
    $file->shouldReceive('getExtension')->andReturn('php');
    $file->shouldReceive('getRealPath')->andReturn($modelPath.'/GoodModel.php');

    File::shouldReceive('isDirectory')->with($modelPath)->andReturn(true);
    File::shouldReceive('allFiles')->with($modelPath)->andReturn([$file]);

    if (! class_exists('App\Models\GoodModel')) {
        eval('namespace App\Models; use Illuminate\Database\Eloquent\Model; class GoodModel extends Model { protected $table = "good_table"; }');
    }

    File::shouldReceive('get')->with($modelPath.'/GoodModel.php')->andReturn('<?php namespace App\Models; class GoodModel extends Model {}');

    $tables = MigrationHelper::getTablesFromModels($projectContext);
    expect($tables)->toContain('good_table');
});

test('getTablesFromModels handles exception in reflection', function () {
    $projectContext = Mockery::mock(ProjectContext::class);
    $modelPath = '/root/app/Models';
    $projectContext->shouldReceive('getModelPath')->andReturn($modelPath);

    $file = Mockery::mock();
    $file->shouldReceive('getExtension')->andReturn('php');
    $file->shouldReceive('getRealPath')->andReturn($modelPath.'/ExceptionModel.php');

    File::shouldReceive('isDirectory')->with($modelPath)->andReturn(true);
    File::shouldReceive('allFiles')->with($modelPath)->andReturn([$file]);
    File::shouldReceive('get')->with($modelPath.'/ExceptionModel.php')->andReturn('<?php namespace App\Models; class ExceptionModel {}');

    // Define a class that will throw during instantiation
    if (! class_exists('App\Models\ExceptionModel')) {
        eval('namespace App\Models; class ExceptionModel extends \Illuminate\Database\Eloquent\Model { public function __construct() { throw new \Exception("Instantiate Error"); } }');
    }

    $tables = MigrationHelper::getTablesFromModels($projectContext);
    expect($tables)->toBeEmpty();
});

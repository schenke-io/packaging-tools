<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Enums\SetupMessages;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\ProjectContext;
use SchenkeIo\PackagingTools\Setup\SqlCache;

describe('SqlCache', function () {
    it('does nothing if sql-cache is disabled', function () {
        $filesystem = Mockery::mock(Filesystem::class);
        $projectContext = new ProjectContext(['projectRoot' => '.', 'sourceRoot' => 'src'], $filesystem);

        $filesystem->shouldReceive('exists')->with('./composer.json')->andReturn(true);
        $filesystem->shouldReceive('get')->with('./composer.json')->andReturn(json_encode(['name' => 'test/test', 'type' => 'library']));
        $filesystem->shouldReceive('exists')->with('./.packaging-tools.neon')->andReturn(true);
        $filesystem->shouldReceive('get')->with('./.packaging-tools.neon')->andReturn('sql-cache: false');

        Config::$outputHandler = function ($message, ...$args) {
            // should not be called
        };

        SqlCache::dump(null, $projectContext);

        expect(true)->toBeTrue();
        Config::$outputHandler = null;
    });

    it('creates a dump if sql-cache is enabled', function () {
        $filesystem = Mockery::mock(Filesystem::class);

        $filesystem->shouldReceive('isDirectory')->andReturn(true);
        $filesystem->shouldReceive('exists')->with('./composer.json')->andReturn(true);
        $filesystem->shouldReceive('get')->with('./composer.json')->andReturn(json_encode(['name' => 'test/test', 'type' => 'library']));

        /** @var ProjectContext $projectContext */
        $projectContext = Mockery::mock(ProjectContext::class, [['projectRoot' => '.', 'sourceRoot' => 'src'], $filesystem])->makePartial();
        $projectContext->filesystem = $filesystem;
        $projectContext->projectRoot = '.';

        $filesystem->shouldReceive('exists')->with('./.packaging-tools.neon')->andReturn(true);
        $filesystem->shouldReceive('get')->with('./.packaging-tools.neon')->andReturn('sql-cache: true');

        $projectContext->shouldReceive('fullPath')->with('tests/Data/seeded.sql')->andReturn('./tests/Data/seeded.sql');
        $projectContext->shouldReceive('getEnv')->with('DB_DATABASE')->andReturn('database/database.sqlite');

        $filesystem->shouldReceive('isAbsolute')->with('database/database.sqlite')->andReturn(false);
        $projectContext->shouldReceive('fullPath')->with('database/database.sqlite')->andReturn('./database/database.sqlite');
        $filesystem->shouldReceive('exists')->with('./database/database.sqlite')->andReturn(true);

        $filesystem->shouldReceive('isDirectory')->with('./tests/Data')->andReturn(true);

        $projectContext->shouldReceive('runProcess')->once()->andReturn(true);

        $outputCalled = false;
        Config::$outputHandler = function ($message, ...$args) use (&$outputCalled) {
            if ($message === SetupMessages::sqlCacheDumped && $args[0] === 'tests/Data/seeded.sql') {
                $outputCalled = true;
            }
        };

        SqlCache::dump(null, $projectContext);

        expect($outputCalled)->toBeTrue();
        Config::$outputHandler = null;
    });

    it('does nothing if no database file is found', function () {
        $filesystem = Mockery::mock(Filesystem::class);

        $filesystem->shouldReceive('exists')->with('./composer.json')->andReturn(true);
        $filesystem->shouldReceive('get')->with('./composer.json')->andReturn(json_encode(['name' => 'test/test', 'type' => 'library']));

        /** @var ProjectContext $projectContext */
        $projectContext = Mockery::mock(ProjectContext::class, [['projectRoot' => '.', 'sourceRoot' => 'src'], $filesystem])->makePartial();
        $projectContext->filesystem = $filesystem;
        $projectContext->projectRoot = '.';

        $filesystem->shouldReceive('exists')->with('./.packaging-tools.neon')->andReturn(true);
        $filesystem->shouldReceive('get')->with('./.packaging-tools.neon')->andReturn('sql-cache: true');

        $projectContext->shouldReceive('getEnv')->with('DB_DATABASE')->andReturn(null);
        $filesystem->shouldReceive('exists')->with('./database/database.sqlite')->andReturn(false);
        $filesystem->shouldReceive('exists')->with('./workbench/database/database.sqlite')->andReturn(false);

        $projectContext->shouldNotReceive('runProcess');

        SqlCache::dump(null, $projectContext);

        expect(true)->toBeTrue();
    });

    it('creates the directory if it does not exist', function () {
        $filesystem = Mockery::mock(Filesystem::class);

        $filesystem->shouldReceive('exists')->with('./composer.json')->andReturn(true);
        $filesystem->shouldReceive('get')->with('./composer.json')->andReturn(json_encode(['name' => 'test/test', 'type' => 'library']));

        /** @var ProjectContext $projectContext */
        $projectContext = Mockery::mock(ProjectContext::class, [['projectRoot' => '.', 'sourceRoot' => 'src'], $filesystem])->makePartial();
        $projectContext->filesystem = $filesystem;
        $projectContext->projectRoot = '.';

        $filesystem->shouldReceive('exists')->with('./.packaging-tools.neon')->andReturn(true);
        $filesystem->shouldReceive('get')->with('./.packaging-tools.neon')->andReturn('sql-cache: true');

        $projectContext->shouldReceive('fullPath')->with('tests/Data/seeded.sql')->andReturn('./tests/Data/seeded.sql');
        $projectContext->shouldReceive('getEnv')->with('DB_DATABASE')->andReturn('database/database.sqlite');
        $filesystem->shouldReceive('isAbsolute')->with('database/database.sqlite')->andReturn(false);
        $projectContext->shouldReceive('fullPath')->with('database/database.sqlite')->andReturn('./database/database.sqlite');
        $filesystem->shouldReceive('exists')->with('./database/database.sqlite')->andReturn(true);

        $filesystem->shouldReceive('isDirectory')->with('./tests/Data')->andReturn(false);
        $filesystem->shouldReceive('makeDirectory')->with('./tests/Data', 0755, true)->once();

        $projectContext->shouldReceive('runProcess')->once()->andReturn(true);

        Config::$outputHandler = function () {};

        SqlCache::dump(null, $projectContext);

        expect(true)->toBeTrue();
        Config::$outputHandler = null;
    });

    it('uses a custom path if sql-cache is a string', function () {
        $filesystem = Mockery::mock(Filesystem::class);

        $filesystem->shouldReceive('exists')->with('./composer.json')->andReturn(true);
        $filesystem->shouldReceive('get')->with('./composer.json')->andReturn(json_encode(['name' => 'test/test', 'type' => 'library']));

        /** @var ProjectContext $projectContext */
        $projectContext = Mockery::mock(ProjectContext::class, [['projectRoot' => '.', 'sourceRoot' => 'src'], $filesystem])->makePartial();
        $projectContext->filesystem = $filesystem;
        $projectContext->projectRoot = '.';

        $filesystem->shouldReceive('exists')->with('./.packaging-tools.neon')->andReturn(true);
        $filesystem->shouldReceive('get')->with('./.packaging-tools.neon')->andReturn('sql-cache: custom/path.sql');

        $projectContext->shouldReceive('fullPath')->with('custom/path.sql')->andReturn('./custom/path.sql');
        $projectContext->shouldReceive('getEnv')->with('DB_DATABASE')->andReturn('database/database.sqlite');
        $filesystem->shouldReceive('isAbsolute')->with('database/database.sqlite')->andReturn(false);
        $projectContext->shouldReceive('fullPath')->with('database/database.sqlite')->andReturn('./database/database.sqlite');
        $filesystem->shouldReceive('exists')->with('./database/database.sqlite')->andReturn(true);

        $filesystem->shouldReceive('isDirectory')->with('./custom')->andReturn(true);

        $projectContext->shouldReceive('runProcess')->once()->andReturn(true);

        $outputCalled = false;
        Config::$outputHandler = function ($message, ...$args) use (&$outputCalled) {
            if ($message === SetupMessages::sqlCacheDumped && $args[0] === 'custom/path.sql') {
                $outputCalled = true;
            }
        };

        SqlCache::dump(null, $projectContext);

        expect($outputCalled)->toBeTrue();
        Config::$outputHandler = null;
    });
});

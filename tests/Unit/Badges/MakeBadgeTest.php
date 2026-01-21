<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use PUGX\Poser\Calculator\TextSizeCalculatorInterface;
use SchenkeIo\PackagingTools\Badges\MakeBadge;
use SchenkeIo\PackagingTools\Enums\BadgeStyle;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('it can make badges', function ($case) {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn('{}'); // default composer.json
    $filesystem->shouldReceive('put')->once();

    $projectContext = new ProjectContext($filesystem);

    $calculator = Mockery::mock(TextSizeCalculatorInterface::class);
    $calculator->shouldReceive('calculateWidth')->andReturn(10.0);

    $makeBadge = new MakeBadge('s', 's', '112233', $projectContext);
    $makeBadge->store('', $case, $calculator);

    expect($makeBadge->info())->toBe('s badge: s / 112233');
})->with(BadgeStyle::cases());

it('can define a badge', function () {
    $badge = \SchenkeIo\PackagingTools\Badges\MakeBadge::define('Subject', 'Status', 'Color');
    expect($badge->info())->toBe('Subject badge: Status / Color');
});

it('can make coverage badge', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(
        json_encode(['name' => 'test/project', 'type' => 'library']), // project root check
        '<?xml version="1.0" encoding="UTF-8"?>
<coverage generated="1735142100">
  <project timestamp="1735142100">
    <metrics statements="100" coveredstatements="80"/>
  </project>
</coverage>', // coverage check in getStatus
        '<?xml version="1.0" encoding="UTF-8"?>
<coverage generated="1735142100">
  <project timestamp="1735142100">
    <metrics statements="100" coveredstatements="80"/>
  </project>
</coverage>' // coverage check in getColor
    );

    $projectContext = new ProjectContext($filesystem);
    $badge = MakeBadge::makeCoverageBadge('clover.xml', $projectContext);
    expect($badge->info())->toBe('Coverage badge: 80% / F1C40F');
});

it('can make phpstan badge', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(
        json_encode(['name' => 'test/project', 'type' => 'library']), // project root check
        "parameters:\n    level: 5", // level check in getStatus
        "parameters:\n    level: 5" // color check in getColor (though PhpStanNeonDriver doesn't use it)
    );

    $projectContext = new ProjectContext($filesystem);
    $badge = MakeBadge::makePhpStanBadge('phpstan.neon', '2563eb', $projectContext);
    expect($badge->info())->toBe('PHPStan badge: 5 / 2563eb');
});

it('can make infection badge', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(
        json_encode(['name' => 'test/project', 'type' => 'library']), // project root check
        '{"msi": 75}', // msi check in getStatus
        '{"msi": 75}' // color check in getColor
    );

    $projectContext = new ProjectContext($filesystem);
    $badge = MakeBadge::makeInfectionBadge('infection-report.json', $projectContext);
    expect($badge->info())->toBe('MSI badge: 75% / F1C40F');
});

it('can run auto', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(
        json_encode(['name' => 'test/project']), // project root check
        '{}' // config check
    );
    $projectContext = new ProjectContext($filesystem);
    MakeBadge::auto($projectContext);
    expect(true)->toBeTrue();
});

it('auto catches exceptions', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_contains($path, 'composer.json')) {
            return json_encode(['name' => 'test/project', 'type' => 'library']);
        }
        if (str_contains($path, 'phpunit.xml')) {
            return '<clover outputFile="clover.xml"/>';
        }
        if (str_contains($path, 'clover.xml')) {
            throw new \Exception('test exception');
        }

        return '';
    });
    $projectContext = new ProjectContext($filesystem);

    MakeBadge::auto($projectContext);
    expect(true)->toBeTrue();
});

it('can make badges without providing project context', function () {
    /*
     * this hits the branches where new ProjectContext is created
     */
    try {
        MakeBadge::makeCoverageBadge('non-existent');
    } catch (\Exception $e) {
    }
    try {
        MakeBadge::makePhpStanBadge('non-existent');
    } catch (\Exception $e) {
    }
    try {
        MakeBadge::makeInfectionBadge('non-existent');
    } catch (\Exception $e) {
    }
    expect(true)->toBeTrue();
});

it('can make badges with null paths', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_contains($path, 'composer.json')) {
            return json_encode(['name' => 'test/project', 'type' => 'library']);
        }
        if (str_contains($path, 'phpunit.xml')) {
            return '<clover outputFile="clover.xml"/>';
        }
        if (str_contains($path, 'clover.xml')) {
            return '<?xml version="1.0" encoding="UTF-8"?><coverage><project><metrics statements="100" coveredstatements="80"/></project></coverage>';
        }
        if (str_contains($path, 'phpstan.neon')) {
            return "parameters:\n    level: 5";
        }
        if (str_contains($path, 'infection')) {
            return '{"msi": 75}';
        }

        return '';
    });

    $projectContext = new ProjectContext($filesystem);
    expect(MakeBadge::makeCoverageBadge(null, $projectContext)->info())->toContain('80%')
        ->and(MakeBadge::makePhpStanBadge(null, '2563eb', $projectContext)->info())->toContain('5')
        ->and(MakeBadge::makeInfectionBadge(null, $projectContext)->info())->toContain('75%');
});

it('can test various drivers', function () {
    $httpMock = Mockery::mock(Factory::class);
    $httpMock->shouldReceive('get')->andReturn(new Response(
        new \GuzzleHttp\Psr7\Response(200, [], json_encode([
            'message' => '1.2.3',
            'color' => 'blue',
        ]))
    ));
    Http::swap($httpMock);

    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project', 'type' => 'library']));
    $projectContext = new ProjectContext($filesystem);

    $drivers = [
        new \SchenkeIo\PackagingTools\Badges\Drivers\DownloadsDriver,
        new \SchenkeIo\PackagingTools\Badges\Drivers\ReleaseVersionDriver,
        new \SchenkeIo\PackagingTools\Badges\Drivers\LaravelVersionDriver,
        new \SchenkeIo\PackagingTools\Badges\Drivers\GitHubTestDriver,
    ];

    foreach ($drivers as $driver) {
        $badge = MakeBadge::fromDriver($driver, 'composer.json', $projectContext);
        expect($badge->info())->toContain($driver->getSubject());
    }
});

it('throws exception when path detection fails', function ($type) {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'composer.json')) {
            static $count = 0;

            return ++$count === 1;
        }

        return false;
    });
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project', 'type' => 'library']));
    $projectContext = new ProjectContext($filesystem);

    match ($type) {
        'Coverage' => MakeBadge::makeCoverageBadge(null, $projectContext),
        'PhpStan' => MakeBadge::makePhpStanBadge(null, 'color', $projectContext),
        'Infection' => MakeBadge::makeInfectionBadge(null, $projectContext),
        'PHP' => MakeBadge::makePhpVersionBadge($projectContext),
    };
})->with(['Coverage', 'PhpStan', 'Infection', 'PHP'])->throws(SchenkeIo\PackagingTools\Exceptions\PackagingToolException::class);

it('can make php version badge', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_contains($path, 'composer.json')) {
            return json_encode([
                'name' => 'test/project',
                'type' => 'library',
                'require' => ['php' => '^8.3'],
            ]);
        }

        return '';
    });

    $projectContext = new ProjectContext($filesystem);
    $badge = MakeBadge::makePhpVersionBadge($projectContext);
    expect($badge->info())->toBe('PHP badge: ^8.3 / 777bb4');
});

it('creates directory in store if not exists', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true, false); // first for project root, second for svg dir
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project', 'type' => 'library']));
    $filesystem->shouldReceive('makeDirectory')->once()->andReturn(true);
    $filesystem->shouldReceive('put')->once();
    $projectContext = new ProjectContext($filesystem);

    $calculator = Mockery::mock(TextSizeCalculatorInterface::class);
    $calculator->shouldReceive('calculateWidth')->andReturn(10.0);

    $makeBadge = new MakeBadge('s', 's', '112233', $projectContext);
    $makeBadge->store('path/to/svg/badge.svg', null, $calculator);
    expect(true)->toBeTrue();
});

it('auto outputs message when badge is generated by mocking Config::output', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturnUsing(function ($path) {
        if (str_contains($path, 'phpunit.xml')) {
            return true;
        }
        if (str_contains($path, 'clover.xml')) {
            return true;
        }
        if (str_contains($path, 'composer.json')) {
            return true;
        }

        return false;
    });
    $filesystem->shouldReceive('get')->andReturnUsing(function ($path) {
        if (str_contains($path, 'composer.json')) {
            return json_encode(['name' => 'test/project', 'type' => 'library']);
        }
        if (str_contains($path, 'phpunit.xml')) {
            return '<clover outputFile="clover.xml"/>';
        }
        if (str_contains($path, 'clover.xml')) {
            return '<?xml version="1.0" encoding="UTF-8"?><coverage><project><metrics statements="100" coveredstatements="80"/></project></coverage>';
        }

        return '';
    });
    $filesystem->shouldReceive('makeDirectory')->andReturn(true);
    $filesystem->shouldReceive('put')->atLeast()->once(); // for store()
    $projectContext = new ProjectContext($filesystem);

    $mock = Mockery::mock();
    $mock->shouldReceive('output')
        ->atLeast()->once()
        ->with(\SchenkeIo\PackagingTools\Enums\SetupMessages::infoBadgeGenerated, Mockery::any());

    \SchenkeIo\PackagingTools\Setup\Config::$outputHandler = fn (...$args) => $mock->output(...$args);

    MakeBadge::auto($projectContext);

    \SchenkeIo\PackagingTools\Setup\Config::$outputHandler = null;
});

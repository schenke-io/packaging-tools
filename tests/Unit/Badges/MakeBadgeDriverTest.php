<?php

use Illuminate\Filesystem\Filesystem;
use PUGX\Poser\Calculator\TextSizeCalculatorInterface;
use SchenkeIo\PackagingTools\Badges\Drivers\CloverCoverageDriver;
use SchenkeIo\PackagingTools\Badges\Drivers\InfectionDriver;
use SchenkeIo\PackagingTools\Badges\Drivers\PhpStanNeonDriver;
use SchenkeIo\PackagingTools\Badges\MakeBadge;
use SchenkeIo\PackagingTools\Enums\BadgeStyle;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

beforeEach(function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $this->projectContext = new ProjectContext($filesystem);
});

it('can make coverage badge from driver', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']), '<project><metrics files="1" statements="100" coveredstatements="80"/></project>', '<project><metrics files="1" statements="100" coveredstatements="80"/></project>');
    $projectContext = new ProjectContext($filesystem);

    $badge = MakeBadge::fromDriver(new CloverCoverageDriver, 'clover.xml', $projectContext);

    // Coverage: 80%
    // Color for 80% should be F1C40F (yellow)
    expect($badge->info())->toBe('Coverage badge: 80% / F1C40F');
});

it('can make PHPStan badge from driver', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']), 'parameters:
    level: 5');
    $projectContext = new ProjectContext($filesystem);

    $badge = MakeBadge::fromDriver(new PhpStanNeonDriver, 'phpstan.neon', $projectContext);

    expect($badge->info())->toBe('PHPStan badge: 5 / 2563eb');
});

it('can store badge to file', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']), '<project><metrics files="1" statements="100" coveredstatements="100"/></project>', '<project><metrics files="1" statements="100" coveredstatements="100"/></project>');
    $filesystem->shouldReceive('put')->once();
    $projectContext = new ProjectContext($filesystem);

    $calculator = Mockery::mock(TextSizeCalculatorInterface::class);
    $calculator->shouldReceive('calculateWidth')->andReturn(10.0);

    $badge = MakeBadge::fromDriver(new CloverCoverageDriver, 'clover.xml', $projectContext);
    $badge->store('badge.svg', BadgeStyle::Plastic, $calculator);
});

it('returns 0 coverage on empty clover file', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']), '');
    $projectContext = new ProjectContext($filesystem);

    $driver = new CloverCoverageDriver;
    expect($driver->getStatus($projectContext, 'clover.xml'))->toBe('0%');
});

it('returns correct color for clover coverage', function ($coverage, $expectedColor) {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(
        json_encode(['name' => 'test/project']),
        sprintf('<project><metrics statements="100" coveredstatements="%d"/></project>', $coverage)
    );
    $projectContext = new ProjectContext($filesystem);

    $driver = new CloverCoverageDriver;
    expect($driver->getColor($projectContext, 'clover.xml'))->toBe($expectedColor);
})->with([
    [95, '27AE60'],
    [80, 'F1C40F'],
    [50, 'C0392B'],
]);

it('returns 0 MSI on empty infection file or missing msi key', function ($content) {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']), $content);
    $projectContext = new ProjectContext($filesystem);

    $driver = new InfectionDriver;
    expect($driver->getStatus($projectContext, 'infection.json'))->toBe('0%');
})->with([
    [''],
    ['{}'],
]);

it('returns correct color for MSI', function ($msi, $expectedColor) {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(
        json_encode(['name' => 'test/project']),
        json_encode(['msi' => $msi])
    );
    $projectContext = new ProjectContext($filesystem);

    $driver = new InfectionDriver;
    expect($driver->getColor($projectContext, 'infection.json'))->toBe($expectedColor);
})->with([
    [85, '27AE60'],
    [70, 'F1C40F'],
    [50, 'C0392B'],
]);

it('returns dash on missing phpstan level', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']), 'parameters:');
    $projectContext = new ProjectContext($filesystem);

    $driver = new PhpStanNeonDriver;
    expect($driver->getStatus($projectContext, 'phpstan.neon'))->toBe('-');
});

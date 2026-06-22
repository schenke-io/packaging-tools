<?php

pest()->group('unit');

use Illuminate\Filesystem\Filesystem;
use Mockery\MockInterface;
use PUGX\Poser\Image;
use PUGX\Poser\Poser;
use SchenkeIo\PackagingTools\Badges\MakeBadge;
use SchenkeIo\PackagingTools\Enums\BadgeStyle;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('falls back to flat style when renderer fails', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn('{}');
    $filesystem->shouldReceive('put')->once();

    $projectContext = new ProjectContext($filesystem);

    /** @var MakeBadge|MockInterface $makeBadge */
    $makeBadge = Mockery::mock(MakeBadge::class, ['subject', 'status', 'color', $projectContext])->makePartial();
    $makeBadge->shouldAllowMockingProtectedMethods();

    $poserMock1 = Mockery::mock(Poser::class);
    $poserMock1->shouldReceive('generate')->once()->andThrow(new InvalidArgumentException('fail'));

    $poserMock2 = Mockery::mock(Poser::class);
    $poserMock2->shouldReceive('generate')->once()->andReturn(Image::createFromString('<svg>fallback</svg>', 'flat'));

    $makeBadge->shouldReceive('getPoser')
        ->twice()
        ->andReturn($poserMock1, $poserMock2);

    $makeBadge->store('path.svg', BadgeStyle::ForTheBadge);

    expect(true)->toBeTrue();
});

<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Badges\BadgeStyle;
use SchenkeIo\PackagingTools\Badges\MakeBadge;

it('it can make badges', function ($case) {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('put')->once();
    $makeBadge = new MakeBadge('s', 's', '112233', $filesystem);
    $makeBadge->store('', $case);
})->with(BadgeStyle::cases());

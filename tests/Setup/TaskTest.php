<?php

use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Requirements;
use SchenkeIo\PackagingTools\Setup\Tasks;

it('has full defined tasks', function (Tasks $case) {
    $filesystem = Mockery::mock(\Illuminate\Filesystem\Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn('[]');

    $config = new Config($filesystem);

    expect($case->definition()->explain())->toBeString()
        ->and($case->definition()->packages($config))->toBeInstanceOf(Requirements::class);

    // assert($case->definition()->explain())->is
})->with(Tasks::cases());

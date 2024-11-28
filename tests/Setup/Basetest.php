<?php

use SchenkeIo\PackagingTools\Setup\Base;

it('can launch the base', function () {
    $base = new Base;
    expect($base)->toBeInstanceOf(Base::class)
        ->and($base->sourceRoot)->toBe('src');
});

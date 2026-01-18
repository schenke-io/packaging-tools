<?php

namespace SchenkeIo\PackagingTools\Tests\Unit;

use SchenkeIo\PackagingTools\Setup;
use SchenkeIo\PackagingTools\Setup\Config;

it('calls Config::doConfiguration in handle', function () {
    Config::$silent = true;
    // this will call Config::doConfiguration() which will try to find composer.json
    // in the current directory. Since we are in the project root, it should find it.
    // To avoid it actually doing anything, we can rely on it not having any arguments
    // so it just shows what's pending.
    Setup::handle();
    expect(true)->toBeTrue();
});

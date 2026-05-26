<?php

namespace SchenkeIo\PackagingTools\Tests\Unit;

pest()->group('unit');

use SchenkeIo\PackagingTools\Setup;
use SchenkeIo\PackagingTools\Setup\Config;

it('calls Config::doConfiguration in handle', function () {
    $oldArgv = $_SERVER['argv'];
    $_SERVER['argv'] = ['vendor/bin/packaging-tools', 'setup'];

    Config::$silent = false;
    ob_start();
    Setup::handle();
    $output = ob_get_clean();
    Config::$silent = true;

    $_SERVER['argv'] = $oldArgv;

    expect($output)->toContain('Everything is up to date.');
});

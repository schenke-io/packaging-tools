<?php

namespace SchenkeIo\PackagingTools;

require 'vendor/autoload.php';  // important

use SchenkeIo\PackagingTools\Setup\Config;

class Setup
{
    public static function handle(): void
    {
        Config::doConfiguration();
    }
}

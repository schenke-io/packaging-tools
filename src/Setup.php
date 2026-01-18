<?php

namespace SchenkeIo\PackagingTools;

require_once 'vendor/autoload.php';  // important

use SchenkeIo\PackagingTools\Setup\Config;

/**
 * Main entry point for the composer-driven setup process.
 *
 * This class handles the initialization and configuration of the packaging tools
 * when triggered by composer events. It serves as a bridge to the Config
 * class which performs the actual configuration logic.
 *
 * Methods:
 * - handle(): Static method called by composer to initiate the setup or update process.
 */
class Setup
{
    public static function handle(mixed $event = null): void
    {
        Config::doConfiguration();
    }
}

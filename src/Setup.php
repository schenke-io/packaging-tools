<?php

namespace SchenkeIo\PackagingTools;

require_once 'vendor/autoload.php';  // important

use SchenkeIo\PackagingTools\Setup\Config;

/**
 * Class Setup
 *
 * Main entry point for the composer-driven setup process.
 *
 * This class handles the initialization and configuration of the packaging tools
 * when triggered by composer events. It serves as a bridge to the Config
 * class which performs the actual configuration logic.
 *
 * Main Responsibilities:
 * - Event Handling: Provides a static `handle()` method for Composer scripts.
 * - Initialization: Triggers the full configuration process through the Config class.
 *
 * Usage Example:
 * ```php
 * SchenkeIo\PackagingTools\Setup::handle();
 * ```
 */
class Setup
{
    public static function handle(mixed $event = null): void
    {
        Config::doConfiguration();
    }
}

<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;

/**
 * Class BadgeDefinition
 *
 * Task definition for generating badges.
 *
 * Main Responsibilities:
 * - Schema Definition: Defines the configuration for enabling automatic badge generation.
 * - Command Execution: Triggers the internal configuration method to generate project badges.
 * - Task Explanation: Provides human-readable help text for badge-related tasks.
 *
 * Usage Example:
 * ```php
 * $badge = new BadgeDefinition();
 * $schema = $badge->schema();
 * ```
 */
class BadgeDefinition extends BaseDefinition
{
    /**
     * Initialize the badge task definition.
     */
    public function __construct()
    {
        parent::__construct('badges');
    }

    /**
     * Return the schema of the configuration for this SetupDefinitionInterface.
     */
    public function schema(): Schema
    {
        return Expect::bool(false);
    }

    /**
     * Return help text for this config key.
     */
    public function explainConfig(): string
    {
        return 'true = enabled (auto-generate badges), false = disabled';
    }

    /**
     * Line or lines which will be executed when the script is called.
     */
    protected function getCommands(Config $config): string|array
    {
        return ['SchenkeIo\\PackagingTools\\Setup\\Config::doConfiguration(null, ["badges"])'];
    }

    /**
     * Return help text for task.
     */
    public function explainTask(): string
    {
        return 'generate SVG badges for project metrics';
    }
}

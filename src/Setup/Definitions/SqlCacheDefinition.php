<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;

/**
 * Class SqlCacheDefinition
 *
 * Definition for the SQL cache task.
 *
 * Main Responsibilities:
 * - Database Dumping: Allows for dumping the SQLite database to an SQL file.
 * - Test Optimization: Facilitates faster testing by avoiding full migrations.
 * - Path Configuration: Supports both default and custom SQL file paths.
 *
 * Usage Example:
 * ```php
 * $sqlCache = new SqlCacheDefinition();
 * $commands = $sqlCache->commands($config);
 * ```
 */
class SqlCacheDefinition extends BaseDefinition
{
    /**
     * Initialize the SQL cache definition.
     */
    public function __construct()
    {
        parent::__construct('sql-cache');
    }

    /**
     * Return the schema of the configuration for this SetupDefinitionInterface.
     */
    public function schema(): Schema
    {
        return Expect::anyOf(Expect::null(), Expect::bool(), Expect::string())->default(null);
    }

    /**
     * Line or lines which will be executed when the script is called.
     *
     * @return array<int, string>
     */
    protected function getCommands(Config $config): string|array
    {
        return ['SchenkeIo\\PackagingTools\\Setup\\SqlCache::dump'];
    }

    /**
     * Return help text for this config key.
     */
    public function explainConfig(): string
    {
        return "null = disabled, true = default path, 'path/to/file.sql' = custom path";
    }

    /**
     * Return help text for task.
     */
    public function explainTask(): string
    {
        return 'dump the current SQLite database to an SQL file for faster testing';
    }
}

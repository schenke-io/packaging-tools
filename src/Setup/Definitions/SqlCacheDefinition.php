<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;

/**
 * Definition for the SQL cache task.
 *
 * This task allows for dumping the SQLite database to an SQL file,
 * which can be used to speed up testing by avoiding full migrations.
 */
class SqlCacheDefinition extends BaseDefinition
{
    public function schema(): Schema
    {
        return Expect::anyOf(Expect::null(), Expect::bool(), Expect::string())->default(null);
    }

    public function getCommands(Config $config): array
    {
        return ['SchenkeIo\\PackagingTools\\Setup\\SqlCache::dump'];
    }

    public function explainConfig(): string
    {
        return "null = disabled, true = default path, 'path/to/file.sql' = custom path";
    }

    public function explainTask(): string
    {
        return 'dump the current SQLite database to an SQL file for faster testing';
    }
}

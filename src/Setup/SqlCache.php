<?php

namespace SchenkeIo\PackagingTools\Setup;

use SchenkeIo\PackagingTools\Enums\SetupMessages;

/**
 * Handles the dumping of SQLite databases to SQL files for test caching.
 *
 * This class provides a static method to dump the current SQLite database
 * to a specified SQL file. This can significantly speed up test execution
 * by allowing tests to load a pre-seeded database state instead of
 * running migrations and seeders repeatedly.
 */
class SqlCache
{
    /**
     * Dumps the current SQLite database to an SQL file.
     *
     * @param  mixed  $event  Optional event from composer or other caller
     * @param  ProjectContext|null  $projectContext  Optional project context
     */
    public static function dump(mixed $event = null, ?ProjectContext $projectContext = null): void
    {
        $projectContext = $projectContext ?? new ProjectContext;
        $config = new Config($projectContext);

        $sqlCacheConfig = $config->config->{'sql-cache'} ?? null;

        if ($sqlCacheConfig === null || $sqlCacheConfig === false) {
            return;
        }

        $sqlPath = is_string($sqlCacheConfig) ? $sqlCacheConfig : 'tests/Data/seeded.sql';

        $dbPathCandidates = [
            $projectContext->getEnv('DB_DATABASE'),
            'database/database.sqlite',
            'workbench/database/database.sqlite',
        ];

        $dbPath = null;
        foreach ($dbPathCandidates as $candidate) {
            if ($candidate && $projectContext->filesystem->exists($projectContext->fullPath($candidate))) {
                $dbPath = $candidate;
                break;
            }
        }

        if (! $dbPath) {
            return;
        }

        $fullDbPath = $projectContext->fullPath($dbPath);
        $fullSqlPath = $projectContext->fullPath($sqlPath);

        $sqlDir = dirname($fullSqlPath);
        if (! $projectContext->filesystem->isDirectory($sqlDir)) {
            $projectContext->filesystem->makeDirectory($sqlDir, 0755, true);
        }

        $command = sprintf('sqlite3 %s .dump > %s', escapeshellarg($fullDbPath), escapeshellarg($fullSqlPath));
        if ($projectContext->runProcess($command)) {
            Config::output(SetupMessages::sqlCacheDumped, $sqlPath);
        }
    }
}

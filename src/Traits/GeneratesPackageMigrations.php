<?php

namespace SchenkeIo\PackagingTools\Traits;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\MigrationCleaner;
use SchenkeIo\PackagingTools\Setup\MigrationHelper;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Trait GeneratesPackageMigrations
 *
 * This trait facilitates the generation and cleaning of package migrations
 * within an Artisan command context. It automates the detection of models
 * and their associated tables, merges them with standard system tables,
 * and runs the migration generator.
 *
 * PHPDoc density requirement (3% rule):
 * This trait is designed to be environment-agnostic and relies on
 * the project's configuration file (.packaging-tools.neon) and
 * the ProjectContext for path resolution. It uses the kitloong
 * migrations generator to reverse-engineer the database schema.
 * It ensures that standard Laravel system tables are always included
 * to provide a consistent base for generated package migrations.
 * The process includes a post-generation cleaning step to remove
 * environment-specific database connection calls from migration files.
 */
trait GeneratesPackageMigrations
{
    /**
     * Build and execute the migrate:generate command.
     */
    public function generatePackageMigrations(?ProjectContext $projectContext = null, ?Config $config = null): void
    {
        if (! $this instanceof Command && ! method_exists($this, 'call')) {
            return;
        }

        $projectContext = $projectContext ?? new ProjectContext;
        $config = $config ?? new Config(null, $projectContext);

        $resolved = MigrationHelper::resolveMigrationTargets($config, $projectContext);
        $path = $projectContext->isWorkbench() ? 'workbench/database/migrations' : 'database/migrations/';

        /*
         * clean up existing migrations
         */
        $fullPath = $projectContext->fullPath($path);
        if (File::isDirectory($fullPath)) {
            File::cleanDirectory($fullPath);
        }

        $arguments = [
            '--no-interaction' => true,
            '--path' => $fullPath,
            '--tables' => implode(',', $resolved['tables']),
            '--default-index-names' => true,
            '--skip-log' => true,
            '--date' => '2020-10-10',
        ];

        if ($resolved['connection'] !== '') {
            $arguments['--connection'] = $resolved['connection'];
        }

        $this->call('migrate:generate', $arguments);

        MigrationCleaner::clean(null, $projectContext);
    }
}

<?php

namespace SchenkeIo\PackagingTools\Setup;

use SchenkeIo\PackagingTools\Enums\SetupMessages;

/**
 * Class MigrationCleaner
 *
 * Cleans up generated migration files.
 *
 * This class provides a utility to post-process migrations generated
 * by external tools (e.g., kitloong/laravel-migrations-generator).
 * Specifically, it removes hardcoded database connection calls like
 * `connection('mysql')->` to ensure migrations remain environment-agnostic
 * and use the default connection of the application where they are run.
 *
 * Main Responsibilities:
 * - Scanning: Identifies all PHP migration files in the project's migration path.
 * - Regex Filtering: Removes `connection('...')` calls using regular expressions.
 * - File Protection: Sets file permissions to read-only (0444) after cleaning.
 * - Feedback: Outputs names of cleaned files to the console.
 *
 * Usage Example:
 * ```php
 * MigrationCleaner::clean();
 * ```
 */
class MigrationCleaner
{
    /**
     * Removes connection('...') calls from migration files in the database/migrations directory.
     *
     * This method is designed to be called either directly or as a composer script.
     */
    public static function clean(mixed $event = null, ?ProjectContext $projectContext = null): int
    {
        $count = 0;
        $projectContext = $projectContext ?? new ProjectContext;
        $directory = $projectContext->fullPath($projectContext->getMigrationPath());

        if (! $projectContext->filesystem->isDirectory($directory)) {
            return 0;
        }

        foreach ($projectContext->filesystem->allFiles($directory) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $content = $projectContext->filesystem->get($file->getRealPath());
            /*
             * remove hardcoded connection() calls
             */
            $cleanedContent = preg_replace('/\bconnection\s*\(\s*(["\'])(?:(?!\1).)*\1\s*\)\s*->/', '', $content);
            if (is_string($cleanedContent) && $content !== $cleanedContent) {
                $projectContext->filesystem->put($file->getRealPath(), $cleanedContent);
                Config::output(SetupMessages::cleanedConnectionCalls, $file->getFilename());
                $count++;
            }
            $projectContext->filesystem->chmod($file->getRealPath(), 0444);
        }

        return $count;
    }
}

<?php

namespace SchenkeIo\PackagingTools\Setup;

use Illuminate\Support\Facades\File;
use SchenkeIo\PackagingTools\Enums\SetupMessages;

/**
 * Cleans up generated migration files.
 *
 * This class provides a utility to post-process migrations generated
 * by external tools (e.g., kitloong/laravel-migrations-generator).
 * Specifically, it removes hardcoded database connection calls like
 * `connection('mysql')->` to ensure migrations remain environment-agnostic
 * and use the default connection of the application where they are run.
 *
 * The `clean()` method scans the `database/migrations` directory and
 * applies a regex-based replacement to all PHP files found there.
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

        if (! File::isDirectory($directory)) {
            return 0;
        }

        foreach (File::allFiles($directory) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $content = File::get($file->getRealPath());
            /*
             * remove hardcoded connection() calls
             */
            $cleanedContent = preg_replace('/\bconnection\s*\(\s*(["\'])(?:(?!\1).)*\1\s*\)\s*->/', '', $content);
            if (is_string($cleanedContent) && $content !== $cleanedContent) {
                File::put($file->getRealPath(), $cleanedContent);
                Config::output(SetupMessages::cleanedConnectionCalls, $file->getFilename());
                $count++;
            }
            File::chmod($file->getRealPath(), 0444);
        }

        return $count;
    }
}

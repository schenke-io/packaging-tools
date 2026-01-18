<?php

namespace SchenkeIo\PackagingTools\Setup;

use Illuminate\Support\Facades\File;

/**
 * Cleans up generated migration files.
 *
 * This class provides a utility to post-process migrations generated
 * by external tools. Specifically, it removes hardcoded database
 * connection calls to ensure migrations remain environment-agnostic.
 */
class MigrationCleaner
{
    /**
     * Removes connection('...') calls from migration files in the database/migrations directory.
     *
     * This method is designed to be called either directly or as a composer script.
     */
    public static function clean(mixed $event = null): int
    {
        $count = 0;
        $projectContext = new ProjectContext;
        $directory = $projectContext->fullPath('database/migrations');

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
            $cleanedContent = preg_replace('/connection\s*\(\s*[\'"][^\'"]*[\'"]\s*\)\s*->/', '', $content);
            if (is_string($cleanedContent) && $content !== $cleanedContent) {
                File::put($file->getRealPath(), $cleanedContent);
                Config::output('Cleaned connection calls from: '.$file->getFilename());
                $count++;
            }
        }

        return $count;
    }
}

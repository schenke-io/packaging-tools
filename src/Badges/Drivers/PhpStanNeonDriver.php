<?php

namespace SchenkeIo\PackagingTools\Badges\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use SchenkeIo\PackagingTools\Contracts\BadgeDriverInterface;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Badge driver for PHPStan static analysis configuration.
 *
 * This driver parses PHPStan neon configuration files to find the analysis level.
 * It provides the status string representing the level and uses a configurable
 * color for the badge. It can automatically detect common PHPStan config files.
 */
class PhpStanNeonDriver implements BadgeDriverInterface
{
    /**
     * @param  string  $color  The hexadecimal color for the badge
     */
    public function __construct(protected string $color = '2563eb') {}

    /**
     * Get the subject for the PHPStan badge.
     */
    public function getSubject(): string
    {
        return 'PHPStan';
    }

    /**
     * Get the PHPStan level found in the neon file.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The path to the PHPStan neon file
     *
     * @throws FileNotFoundException
     */
    public function getStatus(ProjectContext $projectContext, string $path): string
    {
        $content = $projectContext->filesystem->get($projectContext->fullPath($path));
        // search for "level: <number>"
        if (preg_match('/^ *level: *(\d+)/m', $content, $matches)) {
            return $matches[1];
        }

        return '-'; // no level found
    }

    /**
     * Get the badge color.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The path (not used by this driver)
     */
    public function getColor(ProjectContext $projectContext, string $path): string
    {
        return $this->color;
    }

    /**
     * Get the URL for a remote badge.
     *
     * @param  ProjectContext  $projectContext  The project context for file operations
     * @param  string  $path  The path to the source data
     */
    public function getUrl(ProjectContext $projectContext, string $path): ?string
    {
        return null;
    }

    /**
     * Automatically detect the path to the PHPStan configuration file.
     *
     * Scans for phpstan.neon and phpstan.neon.dist.
     *
     * @param  ProjectContext  $projectContext  The project context
     */
    public function detectPath(ProjectContext $projectContext): ?string
    {
        foreach (['phpstan.neon', 'phpstan.neon.dist'] as $file) {
            $path = $projectContext->fullPath($file);
            if ($projectContext->filesystem->exists($path)) {
                return $file;
            }
        }

        return null;
    }
}

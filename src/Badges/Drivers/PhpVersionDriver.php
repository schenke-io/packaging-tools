<?php

namespace SchenkeIo\PackagingTools\Badges\Drivers;

use SchenkeIo\PackagingTools\Contracts\BadgeDriverInterface;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Driver to extract the PHP version from composer.json.
 *
 * This driver looks for the 'php' requirement in the composer.json file
 * and returns the version constraint found. It also provides a link
 * to the Shields.io PHP version badge for the package.
 */
class PhpVersionDriver implements BadgeDriverInterface
{
    /**
     * Get the subject for the PHP version badge.
     */
    public function getSubject(): string
    {
        return 'PHP';
    }

    /**
     * Get the PHP version from composer.json.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The path to composer.json
     */
    public function getStatus(ProjectContext $projectContext, string $path): string
    {
        $composerJson = json_decode($projectContext->composerJsonContent, true);
        $phpVersion = $composerJson['require']['php'] ?? null;

        return $phpVersion ?? 'n/a';
    }

    /**
     * Get the badge color (PHP Blue).
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The path to composer.json
     */
    public function getColor(ProjectContext $projectContext, string $path): string
    {
        return '777bb4'; // PHP Blue
    }

    /**
     * Get the URL for a remote badge from Shields.io.
     *
     * @param  ProjectContext  $projectContext  The project context for file operations
     * @param  string  $path  The path to the source data
     */
    public function getUrl(ProjectContext $projectContext, string $path): ?string
    {
        $packageName = $projectContext->projectName;
        if ($packageName === 'unknown') {
            return null;
        }

        return "https://img.shields.io/packagist/php-v/{$packageName}";
    }

    /**
     * Detection path for PHP version.
     *
     * Returns 'composer.json' if it exists.
     *
     * @param  ProjectContext  $projectContext  The project context
     */
    public function detectPath(ProjectContext $projectContext): ?string
    {
        return $projectContext->filesystem->exists($projectContext->fullPath('composer.json')) ? 'composer.json' : null;
    }
}

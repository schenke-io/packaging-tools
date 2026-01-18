<?php

namespace SchenkeIo\PackagingTools\Badges\Drivers;

use SchenkeIo\PackagingTools\Contracts\BadgeDriverInterface;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Driver to fetch the latest release version from Packagist.
 *
 * This driver connects to the Shields.io API to retrieve the latest
 * stable version for the package as registered on Packagist.
 */
class ReleaseVersionDriver implements BadgeDriverInterface
{
    /**
     * Get the subject for the version badge.
     */
    public function getSubject(): string
    {
        return 'Version';
    }

    /**
     * Get the latest release version status string.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The path (not used by this driver)
     */
    public function getStatus(ProjectContext $projectContext, string $path): string
    {
        return 'n/a';
    }

    /**
     * Get the badge color from the Shields.io response.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The path (not used by this driver)
     */
    public function getColor(ProjectContext $projectContext, string $path): string
    {
        return 'grey';
    }

    /**
     * Get the URL for a remote badge.
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

        return "https://img.shields.io/packagist/v/{$packageName}";
    }

    /**
     * Get the URL for the Packagist page.
     *
     * @param  ProjectContext  $projectContext  The project context for file operations
     * @param  string  $path  The path to the source data
     */
    public function getLinkUrl(ProjectContext $projectContext, string $path): ?string
    {
        return 'https://packagist.org/packages/'.$projectContext->projectName;
    }

    /**
     * Detection path for release version.
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

<?php

namespace SchenkeIo\PackagingTools\Badges\Drivers;

use SchenkeIo\PackagingTools\Contracts\BadgeDriverInterface;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Driver for generating License badges.
 *
 * This driver provides the necessary information to generate a License badge
 * using Shields.io, specifically targeting GitHub repositories. It also
 * provides a direct link to the LICENSE.md file on GitHub.
 */
class LicenseDriver implements BadgeDriverInterface
{
    /**
     * Get the subject (left side) of the badge.
     */
    public function getSubject(): string
    {
        return 'License';
    }

    /**
     * Get the status (right side) of the badge.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The source data path (not used)
     */
    public function getStatus(ProjectContext $projectContext, string $path): string
    {
        return 'n/a';
    }

    /**
     * Get the hexadecimal color for the badge status.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The source data path (not used)
     */
    public function getColor(ProjectContext $projectContext, string $path): string
    {
        return 'grey';
    }

    /**
     * Get the URL for the remote badge from Shields.io.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The source data path (not used)
     */
    public function getUrl(ProjectContext $projectContext, string $path): ?string
    {
        if ($projectContext->repoOwner === 'unknown') {
            return null;
        }

        return sprintf(
            'https://img.shields.io/github/license/%s/%s',
            $projectContext->repoOwner,
            $projectContext->repoName
        );
    }

    /**
     * Get the URL for the license file on GitHub.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The source data path (not used)
     */
    public function getLinkUrl(ProjectContext $projectContext, string $path): ?string
    {
        if ($projectContext->repoOwner === 'unknown') {
            return null;
        }

        return sprintf(
            'https://github.com/%s/%s/blob/main/LICENSE.md',
            $projectContext->repoOwner,
            $projectContext->repoName
        );
    }

    /**
     * Automatically detect the path to the source data.
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

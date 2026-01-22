<?php

namespace SchenkeIo\PackagingTools\Badges\Drivers;

use SchenkeIo\PackagingTools\Contracts\BadgeDriverInterface;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Driver to extract the Laravel version from composer.json.
 *
 * This driver looks for 'laravel/framework' or 'illuminate/*' packages
 * in the composer.json file and returns the version constraint found.
 */
class LaravelVersionDriver implements BadgeDriverInterface
{
    /**
     * Get the subject for the Laravel version badge.
     */
    public function getSubject(): string
    {
        return 'Laravel';
    }

    /**
     * Get the Laravel version from composer.json.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The path to composer.json
     */
    public function getStatus(ProjectContext $projectContext, string $path): string
    {
        $composerJson = json_decode($projectContext->composerJsonContent, true);
        /** @var array<string, string> $require */
        $require = array_merge($composerJson['require'] ?? [], $composerJson['require-dev'] ?? []);

        foreach ($require as $package => $version) {
            if ($package === 'laravel/framework' || str_starts_with($package, 'illuminate/')) {
                // remove non-numeric characters at the beginning except for ^ or ~ or > or <
                return $version;
            }
        }

        return 'n/a';
    }

    /**
     * Get the badge color (Laravel Red).
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The path to composer.json
     */
    public function getColor(ProjectContext $projectContext, string $path): string
    {
        return 'ff2d20'; // Laravel Red
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
     * Detection path for Laravel version.
     *
     * Always returns 'composer.json'.
     *
     * @param  ProjectContext  $projectContext  The project context
     */
    public function detectPath(ProjectContext $projectContext): ?string
    {
        return 'composer.json';
    }
}

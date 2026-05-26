<?php

namespace SchenkeIo\PackagingTools\Badges\Drivers;

use SchenkeIo\PackagingTools\Contracts\BadgeDriverInterface;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Class PhpVersionDriver
 *
 * Driver to extract the PHP version from composer.json.
 *
 * Main Responsibilities:
 * - Version Extraction: Reads the 'php' requirement from composer.json.
 * - Remote Badge Link: Provides URLs for Shields.io PHP version badges.
 * - Detection: Identifies the correct path to the composer.json file.
 *
 * Usage Example:
 * ```php
 * $driver = new PhpVersionDriver();
 * $version = $driver->getStatus($projectContext, 'composer.json');
 * ```
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

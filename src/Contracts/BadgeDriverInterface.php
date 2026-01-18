<?php

namespace SchenkeIo\PackagingTools\Contracts;

use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Interface for badge data drivers
 *
 * This interface defines the contract for classes that provide data for
 * different types of badges. Each driver is responsible for determining
 * the subject, status, and color of the badge, as well as providing
 * logic for automatic detection of the source data path.
 */
interface BadgeDriverInterface
{
    /**
     * Get the subject (left side) of the badge.
     */
    public function getSubject(): string;

    /**
     * Get the status (right side) of the badge based on the provided path.
     *
     * @param  ProjectContext  $projectContext  The project context for file operations
     * @param  string  $path  The path to the source data
     */
    public function getStatus(ProjectContext $projectContext, string $path): string;

    /**
     * Get the hexadecimal color for the badge status.
     *
     * @param  ProjectContext  $projectContext  The project context for file operations
     * @param  string  $path  The path to the source data
     */
    public function getColor(ProjectContext $projectContext, string $path): string;

    /**
     * Get the URL for a remote badge.
     *
     * @param  ProjectContext  $projectContext  The project context for file operations
     * @param  string  $path  The path to the source data
     */
    public function getUrl(ProjectContext $projectContext, string $path): ?string;

    /**
     * Automatically detect the path to the source data within the project.
     *
     * @param  ProjectContext  $projectContext  The project context for file operations
     * @return string|null The detected path or null if not found
     */
    public function detectPath(ProjectContext $projectContext): ?string;
}

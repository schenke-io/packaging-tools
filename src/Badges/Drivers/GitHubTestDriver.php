<?php

namespace SchenkeIo\PackagingTools\Badges\Drivers;

use SchenkeIo\PackagingTools\Contracts\BadgeDriverInterface;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Driver to generate GitHub Action workflow status badge URLs.
 *
 * This driver constructs URLs for GitHub Actions workflow badges. Unlike other
 * drivers, it does not fetch status data via HTTP because GitHub renders
 * the badge (including its color and status text) server-side based on
 * the current workflow state.
 *
 * Mechanics:
 * - Generates URLs ending in /badge.svg for the workflow badge image.
 * - Generates links to the workflow execution page on GitHub.
 * - Uses the workflow filename (e.g., ci.yml) for reliable targeting.
 * - Does NOT perform HTTP requests to fetch status or color.
 *
 * Strategy remarks:
 * - Color and status are handled by GitHub, ensuring they are always in sync.
 * - Using filenames instead of workflow names avoids URL encoding issues.
 *
 * Usage:
 * Typically used via the Badges piece in the MarkdownAssembler to include
 * a dynamic "Tests" badge in the project's documentation.
 */
class GitHubTestDriver implements BadgeDriverInterface
{
    /**
     * Get the subject for the tests badge.
     */
    public function getSubject(): string
    {
        return 'Tests';
    }

    /**
     * Get the workflow status message.
     *
     * Note: This driver does not fetch live status to avoid HTTP overhead.
     * The actual status is rendered by GitHub in the badge image.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The name of the workflow file
     */
    public function getStatus(ProjectContext $projectContext, string $path): string
    {
        return 'n/a';
    }

    /**
     * Get the badge color.
     *
     * Note: This driver does not fetch live color to avoid HTTP overhead.
     * The actual color is rendered by GitHub in the badge image.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The name of the workflow file
     */
    public function getColor(ProjectContext $projectContext, string $path): string
    {
        return 'grey';
    }

    /**
     * Get the URL for a remote badge.
     *
     * Note: This driver does not fetch live data via HTTP; it constructs
     * the URL purely based on project metadata.
     *
     * @param  ProjectContext  $projectContext  The project context for file operations
     * @param  string  $path  The path to the source data
     */
    public function getUrl(ProjectContext $projectContext, string $path): ?string
    {
        $workflow = basename($path);
        if ($projectContext->repoOwner === 'unknown') {
            return null;
        }

        return sprintf(
            'https://github.com/%s/%s/actions/workflows/%s/badge.svg',
            $projectContext->repoOwner,
            $projectContext->repoName,
            $workflow
        );
    }

    /**
     * Get the URL for the workflow actions page.
     *
     * Note: This driver does not fetch live data via HTTP; it constructs
     * the URL purely based on project metadata.
     *
     * @param  ProjectContext  $projectContext  The project context for file operations
     * @param  string  $path  The path to the source data
     */
    public function getLinkUrl(ProjectContext $projectContext, string $path): ?string
    {
        $workflow = basename($path);
        if ($projectContext->repoOwner === 'unknown') {
            return null;
        }

        return sprintf(
            'https://github.com/%s/%s/actions/workflows/%s',
            $projectContext->repoOwner,
            $projectContext->repoName,
            $workflow
        );
    }

    /**
     * Automatically detect the test workflow file.
     *
     * Looks in .github/workflows for any file containing "test" in its name.
     *
     * @param  ProjectContext  $projectContext  The project context
     */
    public function detectPath(ProjectContext $projectContext): ?string
    {
        $workflowsDir = $projectContext->fullPath('.github/workflows');
        if (! $projectContext->filesystem->isDirectory($workflowsDir)) {
            return null;
        }
        $files = $projectContext->filesystem->files($workflowsDir);
        foreach ($files as $file) {
            $filename = basename($file);
            $lowerFilename = strtolower($filename);
            if (str_contains($lowerFilename, 'test') &&
                (str_ends_with($lowerFilename, '.yml') || str_ends_with($lowerFilename, '.yaml'))) {
                return $filename;
            }
        }

        return null;
    }
}

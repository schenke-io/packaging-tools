<?php

namespace SchenkeIo\PackagingTools\Badges\Drivers;

use SchenkeIo\PackagingTools\Contracts\BadgeDriverInterface;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Badge driver for Infection mutation testing reports.
 *
 * This driver parses infection-report.json files to extract the Mutation Score
 * Indicator (MSI). It provides the status string and color based on the
 * mutation score, helping to visualize the quality of the project's tests.
 */
class InfectionDriver implements BadgeDriverInterface
{
    /**
     * Get the subject for the infection badge.
     */
    public function getSubject(): string
    {
        return 'MSI';
    }

    /**
     * Get the MSI percentage status string.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The path to the infection-report.json file
     */
    public function getStatus(ProjectContext $projectContext, string $path): string
    {
        return $this->getMsi($projectContext, $path).'%';
    }

    /**
     * Get the badge color based on MSI percentage.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The path to the infection-report.json file
     */
    public function getColor(ProjectContext $projectContext, string $path): string
    {
        $msi = $this->getMsi($projectContext, $path);
        if ($msi > 80) {
            return '27AE60'; // Green
        } elseif ($msi < 60) {
            return 'C0392B'; // Red
        } else {
            return 'F1C40F'; // Yellow
        }
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
     * Automatically detect the path to the infection report.
     *
     * Scans common locations for infection-report.json.
     *
     * @param  ProjectContext  $projectContext  The project context
     */
    public function detectPath(ProjectContext $projectContext): ?string
    {
        foreach (['infection-report.json', 'build/infection-report.json'] as $file) {
            $path = $projectContext->fullPath($file);
            if ($projectContext->filesystem->exists($path)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Retrieves the MSI percentage from the infection-report.json file.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $filepath  The path to the infection-report.json file
     */
    private function getMsi(ProjectContext $projectContext, string $filepath): int
    {
        $fullPath = $projectContext->fullPath($filepath);
        $content = $projectContext->filesystem->get($fullPath);
        if ($content === '') {
            return 0;
        }

        $data = json_decode($content, true);
        if (isset($data['msi'])) {
            return (int) round($data['msi']);
        }

        return 0;
    }
}

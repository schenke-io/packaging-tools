<?php

namespace SchenkeIo\PackagingTools\Badges\Drivers;

use Illuminate\Support\Facades\Http;
use SchenkeIo\PackagingTools\Contracts\BadgeDriverInterface;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Driver to fetch GitHub Action workflow status.
 *
 * This driver connects to Shields.io to get the status of a
 * specific GitHub Action workflow. It searches for workflow files
 * containing "test" in their name by default.
 */
class GitHubTestDriver implements BadgeDriverInterface
{
    /** @var array<string, mixed>|null */
    protected ?array $cache = null;

    /**
     * Get the subject for the tests badge.
     */
    public function getSubject(): string
    {
        return 'Tests';
    }

    /**
     * Fetch the workflow status from Shields.io.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The name of the workflow file
     * @return array<string, mixed> The response data
     */
    private function fetchData(ProjectContext $projectContext, string $path): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }
        $workflow = basename($path);
        $repo = "{$projectContext->repoOwner}/{$projectContext->repoName}";
        if ($projectContext->repoOwner === 'unknown') {
            return [];
        }

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::get("https://img.shields.io/github/actions/workflow/status/$repo/$workflow.json");
            if ($response->successful()) {
                /** @var array<string, mixed> $json */
                $json = $response->json();
                $this->cache = $json;

                return $this->cache;
            }
        } catch (\Exception $e) {
            // ignore
        }

        return [];
    }

    /**
     * Get the workflow status message.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The name of the workflow file
     */
    public function getStatus(ProjectContext $projectContext, string $path): string
    {
        $data = $this->fetchData($projectContext, $path);

        return $data['message'] ?? 'n/a';
    }

    /**
     * Get the badge color based on the workflow status.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The name of the workflow file
     */
    public function getColor(ProjectContext $projectContext, string $path): string
    {
        $data = $this->fetchData($projectContext, $path);
        $color = (string) ($data['color'] ?? 'grey');

        return match ($color) {
            'brightgreen', 'green' => '27AE60',
            'red' => 'C0392B',
            'yellow' => 'F1C40F',
            default => 'grey'
        };
    }

    /**
     * Get the URL for a remote badge.
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

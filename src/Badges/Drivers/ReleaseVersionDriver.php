<?php

namespace SchenkeIo\PackagingTools\Badges\Drivers;

use Illuminate\Support\Facades\Http;
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
    /** @var array<string, mixed>|null */
    protected ?array $cache = null;

    /**
     * Get the subject for the version badge.
     */
    public function getSubject(): string
    {
        return 'Version';
    }

    /**
     * Fetch the version data from Shields.io.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @return array<string, mixed> The response data
     */
    private function fetchData(ProjectContext $projectContext): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }
        $packageName = $projectContext->projectName;
        if ($packageName === 'unknown') {
            return [];
        }

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::get("https://img.shields.io/packagist/v/{$packageName}.json");
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
     * Get the latest release version status string.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The path (not used by this driver)
     */
    public function getStatus(ProjectContext $projectContext, string $path): string
    {
        $data = $this->fetchData($projectContext);

        return $data['message'] ?? 'n/a';
    }

    /**
     * Get the badge color from the Shields.io response.
     *
     * @param  ProjectContext  $projectContext  The project context
     * @param  string  $path  The path (not used by this driver)
     */
    public function getColor(ProjectContext $projectContext, string $path): string
    {
        $data = $this->fetchData($projectContext);
        $color = (string) ($data['color'] ?? '007ec6');

        return match ($color) {
            'brightgreen', 'green' => '27AE60',
            'red' => 'C0392B',
            'yellow' => 'F1C40F',
            'blue' => '007ec6',
            default => $color
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
     * Always returns 'composer.json'.
     *
     * @param  ProjectContext  $projectContext  The project context
     */
    public function detectPath(ProjectContext $projectContext): ?string
    {
        return 'composer.json';
    }
}

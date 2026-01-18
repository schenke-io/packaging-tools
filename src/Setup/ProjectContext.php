<?php

namespace SchenkeIo\PackagingTools\Setup;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use SchenkeIo\PackagingTools\Exceptions\PackagingToolException;

/**
 * Provides context and metadata for the current project.
 *
 * This class handles the detection of project root, source directory,
 * composer.json content, and repository metadata (owner/name).
 * It also provides helpers for absolute path generation and repository
 * visibility checks. It centralizes filesystem and project-wide state
 * for other setup tasks.
 *
 * Methods:
 * - __construct(): Detects project root, validates composer.json, and determines repo metadata.
 * - fullPath(): Converts a relative path to an absolute path within the project.
 * - isPublicRepository(): Checks if the project's GitHub repository is publicly accessible.
 */
class ProjectContext
{
    public Filesystem $filesystem;

    public string $projectRoot;

    public string $sourceRoot;

    public string $composerJsonPath;

    public string $composerJsonContent;

    public string $projectName;

    public string $repoOwner;

    public string $repoName;

    public function getEnv(string $key, mixed $default = null): mixed
    {
        $envFile = $this->fullPath('.env');
        if ($this->filesystem->exists($envFile)) {
            $content = $this->filesystem->get($envFile);
            if (preg_match("/^{$key}=(.*)$/m", $content, $matches)) {
                return trim($matches[1], " \t\n\r\0\x0B\"'");
            }
        }

        return $default;
    }

    /**
     * @param  Filesystem|array<string,mixed>  $filesystem
     *
     * @throws PackagingToolException
     */
    public function __construct(
        Filesystem|array $filesystem = new Filesystem,
        ?string $projectRoot = null,
        ?string $sourceRoot = null
    ) {
        if (is_array($filesystem)) {
            $data = $filesystem;
            $this->filesystem = new Filesystem;
            $projectRoot = $data['projectRoot'] ?? $projectRoot;
            $sourceRoot = $data['sourceRoot'] ?? $sourceRoot;
        } else {
            $this->filesystem = $filesystem;
        }
        // determine the current project root directory
        $this->projectRoot = $projectRoot ?? (getcwd() ?: throw PackagingToolException::projectRootNotSet());
        if (! $this->filesystem->isDirectory($this->projectRoot)) {
            throw PackagingToolException::projectRootNotFound($this->projectRoot);
        }
        $this->composerJsonPath = $this->projectRoot.'/composer.json';
        // check if composer.json exists in the project root
        if (! $this->filesystem->exists($this->composerJsonPath)) {
            throw PackagingToolException::composerJsonNotFound($this->composerJsonPath);
        }
        $this->composerJsonContent = $this->filesystem->get($this->composerJsonPath);
        $composerJson = json_decode($this->composerJsonContent, true);
        // validate that composer.json is a valid JSON file
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw PackagingToolException::invalidComposerJson(json_last_error_msg());
        }
        $type = $composerJson['type'] ?? '';
        $this->projectName = $composerJson['name'] ?? 'unknown';
        $parts = explode('/', $this->projectName);
        if (count($parts) === 2) {
            $this->repoOwner = $parts[0];
            $this->repoName = $parts[1];
        } else {
            $this->repoOwner = 'unknown';
            $this->repoName = $this->projectName;
        }
        // determine the source directory based on composer project type
        $this->sourceRoot = $sourceRoot ?? ($type == 'project' ? 'app' : 'src');
    }

    /**
     * converts a relative path into a full absolute path from project root
     */
    public function fullPath(string $path): string
    {
        $path = ltrim($path, '/');

        return $this->projectRoot.'/'.$path;
    }

    public function isPublicRepository(): bool
    {
        if ($this->repoOwner === 'unknown') {
            return false;
        }
        $url = "https://github.com/{$this->repoOwner}/{$this->repoName}";

        try {
            $response = Http::head($url);

            return $response instanceof \Illuminate\Http\Client\Response && $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function runProcess(string $command): void
    {
        passthru($command);
    }
}

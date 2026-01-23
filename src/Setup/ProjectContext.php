<?php

namespace SchenkeIo\PackagingTools\Setup;

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Exceptions\PackagingToolException;

/**
 * Provides context and metadata for the current project.
 *
 * This class handles the detection of project root, source directory,
 * composer.json content, and repository metadata (owner/name).
 * It also provides helpers for absolute path generation.
 * It centralizes filesystem and project-wide state for other setup tasks.
 *
 * Methods:
 * - __construct(): Detects project root, validates composer.json, and determines repo metadata.
 * - fullPath(): Converts a relative path to an absolute path within the project.
 * - getModelPath(): Finds the directory where models are stored.
 * - isLaravel(): Checks if the project is a Laravel project.
 * - isWorkbench(): Checks if the project has a workbench directory.
 * - getEnv(): Retrieves an environment variable.
 * - runProcess(): Executes a shell command.
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

    /**
     * @var array<string, mixed>
     */
    public array $composerJson = [];

    /**
     * Retrieves an environment variable from the project's .env file.
     *
     * It handles various quoting styles and trailing comments.
     * Returns the default value if the key is not found or the .env file is missing.
     *
     * @param  string  $key  The environment variable key.
     * @param  mixed  $default  The default value to return if not found.
     * @return mixed The parsed value or the default.
     */
    public function getEnv(string $key, mixed $default = null): mixed
    {
        $envFile = $this->fullPath('.env');
        if ($this->filesystem->exists($envFile)) {
            $content = $this->filesystem->get($envFile);
            if (preg_match("/^{$key}=\s*(.*)$/m", $content, $matches)) {
                $value = trim($matches[1]);
                if ($value === '') {
                    return '';
                }
                // Handle double quotes
                if ($value[0] === '"') {
                    if (preg_match('/^"([^"]*)"/', $value, $quotedMatches)) {
                        return $quotedMatches[1];
                    }
                }
                // Handle single quotes
                if ($value[0] === "'") {
                    if (preg_match("/^'([^']*)'/", $value, $quotedMatches)) {
                        return $quotedMatches[1];
                    }
                }

                // Handle unquoted value with potential trailing comment
                return trim(explode('#', $value, 2)[0]);
            }
        }

        return $default;
    }

    /**
     * Initializes the project context by detecting the root and reading composer.json.
     *
     * It also sets up repository metadata like owner and name.
     *
     * @param  Filesystem|array<string,mixed>  $filesystem  The filesystem instance or initial data.
     * @param  string|null  $projectRoot  Override for the project root path.
     * @param  string|null  $sourceRoot  Override for the source directory path.
     *
     * @throws PackagingToolException If the project root or composer.json is missing or invalid.
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
        $this->projectRoot = rtrim($projectRoot ?? (getcwd() ?: throw PackagingToolException::projectRootNotSet()), '/');
        if (! $this->filesystem->isDirectory($this->projectRoot)) {
            throw PackagingToolException::projectRootNotFound($this->projectRoot);
        }
        $this->composerJsonPath = $this->projectRoot.'/composer.json';
        // check if composer.json exists in the project root
        if (! $this->filesystem->exists($this->composerJsonPath)) {
            throw PackagingToolException::composerJsonNotFound($this->composerJsonPath);
        }
        $this->composerJsonContent = $this->filesystem->get($this->composerJsonPath);
        $decoded = json_decode($this->composerJsonContent, true);
        // validate that composer.json is a valid JSON file
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw PackagingToolException::invalidComposerJson(json_last_error_msg());
        }
        $this->composerJson = $decoded;
        $type = $this->composerJson['type'] ?? '';
        $this->projectName = $this->composerJson['name'] ?? 'unknown';
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
     * Converts a relative path into a full absolute path from the project root.
     *
     * @param  string  $path  The relative path.
     * @return string The absolute path.
     */
    public function fullPath(string $path): string
    {
        $path = ltrim($path, '/');

        return (string) preg_replace('#/+#', '/', $this->projectRoot.'/'.$path);
    }

    /**
     * Returns the absolute path to the first found model directory.
     *
     * It checks workbench/app/Models, app/Models, and src/Models in order.
     *
     * @return string The absolute path to the model directory.
     *
     * @throws PackagingToolException If no model directory is found.
     */
    public function getModelPath(): string
    {
        foreach (['workbench/app/Models', 'app/Models', 'src/Models'] as $dir) {
            $path = $this->fullPath($dir);
            if ($this->filesystem->isDirectory($path)) {
                return $path;
            }
        }

        throw PackagingToolException::modelPathNotFound();
    }

    /**
     * Returns the relative path to the migrations directory based on the environment.
     */
    public function getMigrationPath(): string
    {
        return $this->isWorkbench() ? 'workbench/database/migrations' : 'database/migrations';
    }

    /**
     * Returns true if the project is a Laravel project or uses the laravel/framework package.
     */
    public function isLaravel(): bool
    {
        $type = $this->composerJson['type'] ?? '';

        return $type === 'project' || isset($this->composerJson['require']['laravel/framework']);
    }

    /**
     * Returns true if the project contains a workbench directory.
     */
    public function isWorkbench(): bool
    {
        return $this->filesystem->isDirectory($this->fullPath('workbench'));
    }

    /**
     * Executes a shell command and passes the output to the standard output.
     *
     * @param  string  $command  The command to execute.
     * @return bool True if the command executed successfully (exit code 0).
     */
    public function runProcess(string $command): bool
    {
        passthru($command, $result);

        return $result === 0;
    }
}

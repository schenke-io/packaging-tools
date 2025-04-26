<?php

namespace SchenkeIo\PackagingTools\Setup;

use Illuminate\Filesystem\Filesystem;

class Base
{
    public readonly string $projectRoot;

    public readonly string $sourceRoot;

    public readonly string $composerJsonPath;

    public readonly string $composerJsonContent;

    public readonly string $projectName;

    /**
     * @throws \Exception
     */
    public function __construct(protected Filesystem $filesystem = new Filesystem)
    {
        $this->projectRoot = getcwd() ?: throw new \Exception('Project root is not set');
        if (! $this->filesystem->isDirectory($this->projectRoot)) {
            throw new \Exception('Project root is not a directory: '.$this->projectRoot);
        }
        $this->composerJsonPath = $this->projectRoot.'/composer.json';
        if (! $this->filesystem->exists($this->composerJsonPath)) {
            throw new \Exception('composer.json not found: '.$this->composerJsonPath);
        }
        $this->composerJsonContent = $this->filesystem->get($this->composerJsonPath);
        $composerJson = json_decode($this->composerJsonContent, true);
        $type = $composerJson['type'] ?? '';
        $this->projectName = $composerJson['name'] ?? '';
        $this->sourceRoot = $type == 'project' ? 'app' : 'src';
    }

    protected function fullPath(string $path): string
    {
        $path = ltrim($path, '/');

        return $this->projectRoot.'/'.$path;
    }
}

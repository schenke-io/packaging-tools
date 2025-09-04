<?php

namespace SchenkeIo\PackagingTools\Setup;

use Illuminate\Filesystem\Filesystem;

class Base
{
    protected static \Illuminate\Filesystem\Filesystem $filesystem;

    public readonly string $projectRoot;

    public readonly string $sourceRoot;

    public readonly string $composerJsonPath;

    public readonly string $composerJsonContent;

    public readonly string $projectName;

    /**
     * @throws \Exception
     */
    public function __construct(Filesystem $filesystem = new Filesystem)
    {
        if (! isset(self::$filesystem)) {
            self::$filesystem = $filesystem;
        }
        $this->projectRoot = getcwd() ?: throw new \Exception('Project root is not set');
        if (! self::$filesystem->isDirectory($this->projectRoot)) {
            throw new \Exception('Project root is not a directory: '.$this->projectRoot);
        }
        $this->composerJsonPath = $this->projectRoot.'/composer.json';
        if (! self::$filesystem->exists($this->composerJsonPath)) {
            throw new \Exception('composer.json not found: '.$this->composerJsonPath);
        }
        $this->composerJsonContent = self::$filesystem->get($this->composerJsonPath);
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

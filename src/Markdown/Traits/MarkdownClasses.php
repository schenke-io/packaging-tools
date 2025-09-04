<?php

namespace SchenkeIo\PackagingTools\Markdown\Traits;

use Closure;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use ReflectionException;
use SchenkeIo\PackagingTools\Markdown\ClassReader;

trait MarkdownClasses
{
    /**
     * Extracts documentation of a class in Markdown format
     *
     * @throws ReflectionException
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function addClassMarkdown(string $classname): void
    {
        $this->blocks[] = ClassReader::fromClass($classname)
            ->getClassMarkdown($this->markdownSourceDir);
    }

    /**
     * Uses a glob function to find many classes and extract their documentations
     *
     * @throws ReflectionException
     * @throws FileNotFoundException
     */
    public function addClasses(string $glob): void
    {
        foreach (self::$filesystem->glob($this->fullPath($glob)) as $file) {
            $this->blocks[] = ClassReader::fromPath($file)->getClassMarkdown($this->markdownSourceDir);
        }
    }

    /**
     * adds a custom text using $callback(array $classData)
     *
     * @throws ReflectionException
     */
    public function addCustomClassMarkdown(string $classname, Closure $callback): void
    {
        $this->blocks[] = $callback($this->getClassData($classname));
    }

    /**
     * return the class documentation data as array
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function getClassData(string $classname): array
    {
        return ClassReader::fromClass($classname)->getClassDataFromClass($classname);
    }
}

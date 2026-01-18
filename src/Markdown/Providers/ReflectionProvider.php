<?php

namespace SchenkeIo\PackagingTools\Markdown\Providers;

use SchenkeIo\PackagingTools\Contracts\MarkdownPieceInterface;
use SchenkeIo\PackagingTools\Markdown\ClassReader;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Provides markdown content using reflection on a given class.
 *
 * This provider uses PHP Reflection via the ClassReader to extract
 * information about a specific class and convert it into markdown
 * format. This is typically used to generate documentation directly
 * from the source code's structure and PHPDocs.
 *
 * It implements the MarkdownPieceInterface and uses:
 * - A class name provided via the constructor
 * - ClassReader::fromClass() to analyze the class
 * - getClassMarkdown() to produce the final markdown string
 */
class ReflectionProvider implements MarkdownPieceInterface
{
    /**
     * @param  class-string  $classname
     */
    public function __construct(protected string $classname) {}

    public function getContent(ProjectContext $projectContext, string $markdownSourceDir): string
    {
        return ClassReader::fromClass($this->classname, $projectContext)
            ->getClassMarkdown($markdownSourceDir);
    }
}

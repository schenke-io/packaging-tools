<?php

namespace SchenkeIo\PackagingTools\Contracts;

use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Interface for classes that provide Markdown content.
 *
 * This interface defines a contract for components that generate or supply
 * Markdown blocks for the final documentation assembly. Implementing classes
 * are responsible for producing a string of valid Markdown content using
 * the provided project context and source directory.
 *
 * Methods:
 * - getContent(): Generates the Markdown content based on the project state.
 */
interface MarkdownPieceInterface
{
    public function getContent(ProjectContext $projectContext, string $markdownSourceDir): string;
}

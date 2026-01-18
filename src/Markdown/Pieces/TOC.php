<?php

namespace SchenkeIo\PackagingTools\Markdown\Pieces;

use SchenkeIo\PackagingTools\Contracts\MarkdownPieceInterface;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Piece for generating a Table of Contents from markdown blocks.
 *
 * This class implements the MarkdownPieceInterface and provides
 * functionality to generate a Markdown Table of Contents (TOC)
 * based on a set of provided markdown blocks. It identifies
 * headings (lines starting with #) and creates a nested list
 * of links to those headings.
 *
 * The class works by:
 * - Accepting an array of markdown blocks via setBlocks()
 * - Parsing each block for ATX-style headings (#)
 * - Calculating heading levels based on the number of # characters
 * - Generating markdown list items with internal links to those headings
 */
class TOC implements MarkdownPieceInterface
{
    /**
     * @var array<int, string>
     */
    protected array $blocks = [];

    /**
     * Set the blocks to be used for TOC generation.
     *
     * @param  array<int, string>  $blocks
     */
    public function setBlocks(array $blocks): self
    {
        $this->blocks = $blocks;

        return $this;
    }

    /**
     * Generate the Table of Contents content.
     */
    public function getContent(ProjectContext $projectContext, string $markdownSourceDir): string
    {
        $toc = '';
        foreach ($this->blocks as $block) {
            foreach (explode("\n", $block) as $line) {
                if (preg_match('@^(#+) (.*)$@', $line, $matches)) {
                    [, $level, $headingText] = $matches;
                    $headingLevel = strlen($level) - 1;
                    $link = strtolower(str_replace(' ', '-', $headingText));

                    // Handle heading levels and create TOC entries
                    $toc .= sprintf(
                        "%s* [%s](#%s)\n",
                        str_repeat('  ', $headingLevel),
                        $headingText,
                        $link
                    );
                }
            }
        }

        return $toc;
    }
}

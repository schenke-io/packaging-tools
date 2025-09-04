<?php

namespace SchenkeIo\PackagingTools\Markdown;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Markdown\Traits\MarkdownBadges;
use SchenkeIo\PackagingTools\Markdown\Traits\MarkdownClasses;
use SchenkeIo\PackagingTools\Markdown\Traits\MarkdownTables;
use SchenkeIo\PackagingTools\Markdown\Traits\TableOfContents;
use SchenkeIo\PackagingTools\Setup\Base;

/**
 * Assembler of a markdown file
 *
 * @markdown
 */
class MarkdownAssembler extends Base
{
    public const TOC_FLAG = '<!-- placeholder for the Table of contents -->';

    use MarkdownBadges;
    use MarkdownClasses;
    use MarkdownTables;
    use TableOfContents;

    protected readonly string $sourceText;

    /**
     * @var array<int,string>
     */
    protected array $blocks = [];

    /**
     * @throws Exception
     */
    public function __construct(
        protected readonly string $markdownSourceDir,
        Filesystem $filesystem = new Filesystem
    ) {
        parent::__construct($filesystem);
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $trace[0];

        $this->sourceText = sprintf(<<<'EOM'
<!--

This file was written by '%s' line %d using
%s

Do not edit manually as it will be overwritten.

-->

EOM

            , basename($caller['file'] ?? ''), $caller['line'] ?? 0, $caller['class'] ?? ''
        );

    }

    /**
     * Adds a markdown file.
     *
     * @throws FileNotFoundException
     */
    public function addMarkdown(string $filepath): void
    {
        $this->blocks[] = self::$filesystem
            ->get(
                $this->fullPath($this->markdownSourceDir.'/'.$filepath)
            );
    }

    /**
     * add a table of content for the full file
     */
    public function addTableOfContents(): void
    {
        $this->blocks[] = self::TOC_FLAG;
    }

    /**
     * adds markdown text
     */
    public function addText(string $content): void
    {
        $this->blocks[] = $content;
    }

    /**
     * writes all added elements into one file
     */
    public function writeMarkdown(string $filepath): void
    {
        $content = $this->sourceText;
        foreach ($this->blocks as $block) {
            if ($block === self::TOC_FLAG) {
                $content .= $this->getTableOfContents();
            } else {
                $content .= $block;
            }
            $content .= "\n\n\n";

        }
        self::$filesystem->put($this->fullPath($filepath), $content);
    }
}

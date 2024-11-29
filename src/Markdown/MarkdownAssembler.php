<?php

namespace SchenkeIo\PackagingTools\Markdown;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use ReflectionException;
use SchenkeIo\PackagingTools\Setup\Base;

/**
 * Assembler of a markdown file
 *
 * @markdown
 */
class MarkdownAssembler extends Base
{
    public const TOC_FLAG = '<!-- placeholder for the Table of contents -->';

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
        protected Filesystem $filesystem = new Filesystem
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
        foreach ($this->filesystem->glob($this->fullPath($glob)) as $file) {
            $this->blocks[] = ClassReader::fromPath($file)->getClassMarkdown($this->markdownSourceDir);
        }
    }

    /**
     * Adds a markdown file.
     *
     * @throws FileNotFoundException
     */
    public function addMarkdown(string $filepath): void
    {
        $this->blocks[] = $this->filesystem
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
     * read a csv file and converts it into a table
     *
     * @markdown
     *
     * @throws Exception
     */
    public function addTableFromFile(string $filepath, Filesystem $filesystem = new Filesystem): void
    {
        $this->blocks[] = (new Table)->getTableFromFile($filepath, $filesystem);
    }

    /**
     * takes a csv string and converts it into a table
     */
    public function addTableFromCsvString(string $csv, string $delimiter): void
    {
        $this->blocks[] = (new Table)->addTableFromCsvString($csv, $delimiter);
    }

    /**
     * takes an array and converts it into a table
     *
     * @markdown
     */
    public function addTableFromArray(array $data): void
    {
        $this->blocks[] = (new Table)->getTableFromArray($data);
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
        $this->filesystem->put($this->fullPath($filepath), $content);
    }
}

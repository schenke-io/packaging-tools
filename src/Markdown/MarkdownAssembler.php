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

    protected const FILE_DELIMITER = [
        'csv' => ',',
        'tsv' => "\t",
        'psv' => '|',
    ];

    use TableOfContents;
    use Tables;

    protected readonly string $sourceText;

    /**
     * @var array<int,string>
     */
    protected array $blocks = [];

    /**
     * @var ClassData[]
     */
    protected array $classData = [];

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
     * @throws ReflectionException
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function addClassMarkdown(string $classname): void
    {
        $this->classData[] = ClassReader::fromClass($classname)
            ->getClassMarkdown($this->markdownSourceDir);
    }

    /**
     * @throws ReflectionException
     * @throws FileNotFoundException
     */
    public function addClasses(string $glob): void
    {
        foreach ($this->filesystem->glob($this->fullPath($glob)) as $file) {
            $this->classData[] = ClassReader::fromPath($file)->getClassMarkdown($this->markdownSourceDir);
        }
    }

    /**
     * Adds a markdown component file
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

    public function addTableOfContents(): void
    {
        $this->blocks[] = self::TOC_FLAG;
    }

    public function addText(string $content): void
    {
        $this->blocks[] = $content;
    }

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

<?php

namespace SchenkeIo\PackagingTools\Markdown;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use ReflectionException;

/**
 * Assembler of a markdown file
 *
 * @markdown
 */
class MarkdownAssembler
{
    public const TOC_FLAG = '<!-- placeholder for the Table of contents -->';

    use ClassReflection;
    use TableOfContents;
    use Tables;

    protected readonly string $root;

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
        string $root,
        protected readonly string $mardownSourceDir,
        protected Filesystem $filesystem = new Filesystem
    ) {
        if (! $filesystem->isDirectory($root)) {
            throw new Exception('invalid directory: '.$root);
        }
        $this->root = realpath($root) ?: '';
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
     */
    public function addClasses(string $glob): void
    {
        foreach ($this->filesystem->glob($glob) as $file) {
            $this->classData[] = $this->getClassDataFromFile($file);
        }
    }

    /**
     * Adds a markdown component file
     *
     * @throws FileNotFoundException
     */
    public function addMarkdown(string $filepath): void
    {
        $this->blocks[] = $this->filesystem->get($this->fullMd($filepath));
    }

    public function addTableOfContents(): void
    {
        $this->blocks[] = self::TOC_FLAG;
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
        $this->filesystem->put($this->full($filepath), $content);
    }

    protected function fullMd(string $filepath): string
    {
        return $this->full($this->mardownSourceDir.DIRECTORY_SEPARATOR.$filepath);
    }

    protected function full(string $filepath): string
    {
        return $this->root.DIRECTORY_SEPARATOR.$filepath;
    }
}

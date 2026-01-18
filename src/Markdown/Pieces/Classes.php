<?php

namespace SchenkeIo\PackagingTools\Markdown\Pieces;

use Closure;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use ReflectionException;
use SchenkeIo\PackagingTools\Contracts\MarkdownPieceInterface;
use SchenkeIo\PackagingTools\Exceptions\PackagingToolException;
use SchenkeIo\PackagingTools\Markdown\ClassReader;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Markdown component for generating class documentation.
 *
 * This class provides a fluent interface to specify which PHP classes should
 * be documented in the Markdown output. It leverages the ClassReader to
 * extract PHPDoc and method information, transforming reflection data into
 * formatted Markdown blocks.
 *
 * Support Mechanisms:
 * - Single Class: Explicitly add individual classes by their fully qualified names.
 * - Glob Patterns: Batch-add classes by specifying file path patterns (e.g., src/*.php).
 * - Custom Callbacks: Provide a closure to manually format class metadata for unique layouts.
 *
 * Methods:
 * - add(): Buffer a single class for documentation.
 * - glob(): Buffer multiple classes matching a file system pattern.
 * - custom(): Register a class with a custom rendering callback.
 * - getContent(): Iterates through the buffer to resolve all requests into a final Markdown string.
 */
class Classes implements MarkdownPieceInterface
{
    /**
     * buffer for classes to document
     *
     * @var array<int, array{type: string, value: string, callback?: Closure}>
     */
    protected array $buffer = [];

    /**
     * adds a single class documentation
     *
     * @param  class-string  $classname
     */
    public function add(string $classname): self
    {
        $this->buffer[] = ['type' => 'class', 'value' => $classname];

        return $this;
    }

    /**
     * adds multiple classes documentation via glob pattern
     */
    public function glob(string $glob): self
    {
        $this->buffer[] = ['type' => 'glob', 'value' => $glob];

        return $this;
    }

    /**
     * adds a custom markdown block for a class using a callback
     *
     * @param  class-string  $classname
     */
    public function custom(string $classname, Closure $callback): self
    {
        $this->buffer[] = ['type' => 'custom', 'value' => $classname, 'callback' => $callback];

        return $this;
    }

    /**
     * adds all classes in the src directory
     */
    public function all(): self
    {
        return $this->glob('src/**/*.php');
    }

    /**
     * resolves the buffered requests into a single markdown string
     *
     * @throws ReflectionException
     * @throws FileNotFoundException
     * @throws PackagingToolException
     */
    public function getContent(ProjectContext $projectContext, string $markdownSourceDir): string
    {
        $markdown = [];
        foreach ($this->buffer as $item) {
            switch ($item['type']) {
                case 'class':
                    /** @var class-string $classname */
                    $classname = $item['value'];
                    $markdown[] = ClassReader::fromClass($classname, $projectContext)
                        ->getClassMarkdown($markdownSourceDir);
                    break;
                case 'glob':
                    $pattern = $projectContext->fullPath($item['value']);
                    foreach ($projectContext->filesystem->glob($pattern) as $file) {
                        $markdown[] = ClassReader::fromPath($file, $projectContext)
                            ->getClassMarkdown($markdownSourceDir);
                    }
                    break;
                case 'custom':
                    /** @var class-string $classnameCustom */
                    $classnameCustom = $item['value'];
                    $classReader = ClassReader::fromClass($classnameCustom, $projectContext);
                    $classData = $classReader->getClassDataFromClass($classnameCustom);
                    if (isset($item['callback'])) {
                        $markdown[] = $item['callback']($classData);
                    }
                    break;
            }
        }

        return implode("\n", $markdown);
    }
}

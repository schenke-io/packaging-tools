<?php

namespace SchenkeIo\PackagingTools\Markdown;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Nette\PhpGenerator\PhpFile;
use ReflectionClass;
use ReflectionException;
use SchenkeIo\PackagingTools\Exceptions\PackagingToolException;
use SchenkeIo\PackagingTools\Markdown\Pieces\Tables;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Reads PHP classes and extracts documentation for Markdown generation.
 *
 * This class uses reflection to inspect PHP classes and extract PHPDoc blocks,
 * methods, and properties. It converts this information into Markdown format,
 * supporting external Markdown file inclusion and automatic table generation
 * for class methods.
 *
 * It provides mechanisms to:
 * - Extract class summaries and descriptions from PHPDoc.
 * - Format class properties and public methods into structured Markdown lists and tables.
 * - Integrate external .md files based on class and method names.
 * - Handle @markdown tags within class documentation to trigger specialized inclusion logic.
 *
 * Key Methods:
 * - fromClass() / fromPath(): Factory methods to instantiate the reader for a specific class.
 * - getClassMarkdown(): The primary method to generate a full Markdown block for a class.
 * - getClassDataFromClass(): Lower-level reflection logic to collect class-string metadata.
 */
class ClassReader
{
    /**
     * @param  class-string  $classname
     */
    public function __construct(
        public string $classname,
        protected ProjectContext $projectContext = new ProjectContext
    ) {}

    /**
     * @param  class-string  $classname
     *
     * @throws PackagingToolException
     */
    public static function fromClass(string $classname, ?ProjectContext $projectContext = null): self
    {
        return new self($classname, $projectContext ?? new ProjectContext);
    }

    /**
     * @throws FileNotFoundException
     */
    public static function fromPath(string $filepath, ?ProjectContext $projectContext = null): self
    {
        $projectContext = $projectContext ?? new ProjectContext;
        $file = PhpFile::fromCode($projectContext->filesystem->get($filepath));
        $classes = $file->getClasses();
        /** @var class-string $classname */
        $classname = (string) array_key_first($classes);

        return new self($classname, $projectContext);
    }

    /**
     * @throws ReflectionException|FileNotFoundException
     */
    public function getClassMarkdown(string $markdownSourceDir, int $headerLevel = 3): string
    {
        $classData = $this->getClassDataFromClass($this->classname);

        $return = '';
        // Add class name as header
        $return .= str_repeat('#', $headerLevel).' '.$classData['short']."\n\n";
        // Add class summary
        $return .= $classData['summary']."\n\n";

        /*
         * Properties: extract and format them as list
         */
        $properties = [];
        foreach ($classData['property'] ?? [] as $propertyLine) {
            // match format: type $name description
            if (preg_match('/^(.*?)\$(.*?) (.*)/', $propertyLine, $matches)) {
                $properties[] = sprintf("* __\$%s:__ %s\n", $matches[2], $matches[3]);
            }
        }
        if (count($properties) > 0) {
            $return .= str_repeat('#', $headerLevel + 1)." Properties\n\n";
            $return .= implode("\n", $properties);
        }
        /*
         * Methods from header: these can overwrite the docs on the method itself (useful for traits)
         */
        $methods = [];
        foreach ($classData['method'] ?? [] as $methodLine) {
            // match format: method() description
            if (preg_match('/^(.*?)\(\)(.*)/', $methodLine, $matches)) {
                $methods[trim($matches[1])] = trim($matches[2]);
            }
        }
        /*
         * External markdown files inclusion
         */
        if ($classData['markdown'] > 0) {
            // @markdown tag was found in class doc
            $return .= $this->projectContext->filesystem->get(
                $this->projectContext->fullPath($markdownSourceDir.'/'.$classData['markdown-file'])
            );
        }
        if (count($classData['methods']) > 0) {
            $return .= str_repeat('#', $headerLevel + 1).' Public methods of '.$classData['short']."\n\n";
            /*
             * build the main methods table
             */
            $tableData = [];
            $tableData[] = ['method', 'summary'];
            foreach ($classData['methods'] as $shortMethod => $methodData) {
                // use method summary or fallback to header-defined method description
                $details = strlen($methodData['summary']) > 3 ? $methodData['summary'] : $methods[$shortMethod] ?? '-';
                $tableData[] = [$shortMethod, $details];
            }
            $return .= (new Tables)->getTableFromArray($tableData);
            /*
             * find and include method-specific markdown files
             */
            foreach ($classData['methods'] as $shortMethod => $methodData) {
                if ($methodData['markdown'] > 0) {
                    $return .= str_repeat('#', $headerLevel + 1)." Details of $shortMethod()\n\n";
                    $return .= $this->projectContext->filesystem->get(
                        $this->projectContext->fullPath(
                            $markdownSourceDir.
                            '/'.
                            substr($classData['markdown-file'], 0, -3).
                            '/'.
                            "$shortMethod.md"
                        )
                    );
                }
            }

        }

        return $return;
    }

    /**
     * @param  class-string  $classname
     * @return array<string,mixed>
     *
     * @throws ReflectionException
     */
    public function getClassDataFromClass(string $classname): array
    {
        $reflection = new ReflectionClass($classname);
        $doc = $reflection->getDocComment();

        $classParts = explode('\\', $classname);
        // reduce array removing the first 2 parts
        $classParts = array_slice($classParts, 2);

        $return = array_merge([
            'summary' => 'empty',
            'description' => '',
            'sources' => [],
            'markdown' => 0,
        ], PhpDocExtractor::getFrom($doc ?: ''));
        $return['classname'] = $classname;
        $return['short'] = $reflection->getShortName();
        $return['filePath'] = $reflection->getFileName();
        $return['isFinal'] = $reflection->isFinal();
        $return['markdown-file'] = implode('/', $classParts).'.md';
        $return['methods'] = [];
        /*
         * add public method names
         */
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();
            $declaringClass = $method->getDeclaringClass();

            // Exclude methods that are not declared in the current class or its parents (not traits)
            if ($declaringClass->getName() !== $reflection->getName() && ! $declaringClass->isTrait()) {
                continue;
            }

            // Exclude methods that come from a trait
            if ($declaringClass->isTrait()) {
                continue;
            }

            if (str_starts_with($methodName, '_')) {
                continue;
            }
            // now we have all public method without _ prefix defined in this class
            $return['methods'][$methodName] = array_merge([
                'summary' => 'empty',
                'description' => '',
                'markdown' => 0,
            ],
                PhpDocExtractor::getFrom($method->getDocComment() ?: '')
            );

        }

        return $return;
    }
}

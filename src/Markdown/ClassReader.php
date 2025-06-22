<?php

namespace SchenkeIo\PackagingTools\Markdown;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Nette\PhpGenerator\PhpFile;
use ReflectionClass;
use ReflectionException;
use SchenkeIo\PackagingTools\Setup\Base;

class ClassReader extends Base
{
    public function __construct(public string $classname, Filesystem $filesystem = new Filesystem)
    {
        parent::__construct($filesystem);
    }

    /**
     * @throws \Exception
     */
    public static function fromClass(string $classname): self
    {
        return new self($classname);
    }

    /**
     * @throws FileNotFoundException
     */
    public static function fromPath(string $filepath): self
    {
        $file = PhpFile::fromCode(file_get_contents($filepath));

        return new self(array_key_first($file->getClasses()));
    }

    /**
     * @throws ReflectionException|FileNotFoundException
     */
    public function getClassMarkdown(string $markdownSourceDir, int $headerLevel = 3): string
    {
        $classData = $this->getClassDataFromClass($this->classname);

        $return = '';
        $return .= str_repeat('#', $headerLevel).' '.$classData['short']."\n\n";
        $return .= $classData['summary']."\n\n";

        /*
         * Properties
         */
        $properties = [];
        foreach ($classData['property'] ?? [] as $propertyLine) {
            if (preg_match('/^(.*?)\$(.*?) (.*)/', $propertyLine, $matches)) {
                $properties[] = sprintf("* __\$%s:__ %s\n", $matches[2], $matches[3]);
            }
        }
        if (count($properties) > 0) {
            $return .= str_repeat('#', $headerLevel + 1)." Properties\n\n";
            $return .= implode("\n", $properties);
        }
        /*
         * Methods from header overwrite the docs on the method itself (usefull for traits)
         */
        $methods = [];
        foreach ($classData['method'] ?? [] as $methodLine) {
            if (preg_match('/^(.*?)\(\)(.*)/', $methodLine, $matches)) {
                $methods[trim($matches[1])] = trim($matches[2]);
            }
        }
        /*
         * external files
         */
        if ($classData['markdown'] > 0) {
            $return .= $this->filesystem->get(
                $this->fullPath($markdownSourceDir.'/'.$classData['markdown-file'])
            );
        }
        if (count($classData['methods']) > 0) {
            $return .= str_repeat('#', $headerLevel + 1).' Public methods of '.$classData['short']."\n\n";
            /*
             * build the main table
             */
            $table[] = ['method', 'summary'];
            foreach ($classData['methods'] as $shortMethod => $methodData) {
                $details = strlen($methodData['summary']) > 3 ? $methodData['summary'] : $methods[$shortMethod] ?? '-';
                $table[] = [$shortMethod, $details];
            }
            $return .= (new Table)->getTableFromArray($table);
            /*
             * find method markdowns
             */
            foreach ($classData['methods'] as $shortMethod => $methodData) {
                if ($methodData['markdown'] > 0) {
                    $return .= str_repeat('#', $headerLevel + 1)." Details of $shortMethod()\n\n";
                    $return .= $this->filesystem->get(
                        $this->fullPath(
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
                PhpDocExtractor::getFrom($method->getDocComment())
            );

        }

        return $return;
    }
}

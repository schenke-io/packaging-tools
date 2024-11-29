<?php

namespace SchenkeIo\PackagingTools\Markdown;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Jasny\PhpdocParser\PhpdocParser;
use Jasny\PhpdocParser\Tag\DescriptionTag;
use Jasny\PhpdocParser\Tag\FlagTag;
use Jasny\PhpdocParser\Tag\MultiTag;
use Jasny\PhpdocParser\Tag\Summery;
use Jasny\PhpdocParser\Tag\WordTag;
use Jasny\PhpdocParser\TagSet;
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
        $base = new base;
        $content = $base->filesystem->get($base->fullPath($filepath));
        $namespace = '';
        $class = '';
        foreach (explode("\n", $content) as $line) {
            if (preg_match('@^namespace (.*?);@', $line, $matches)) {
                $namespace = $matches[1];
            }
            if ($namespace && preg_match('@^class ([a-zA-Z0-9_]*)@', $line, $matches)) {
                $class = $matches[1];
                break;
            }
        }

        return new self("$namespace\\$class");
    }

    /**
     * @throws ReflectionException|FileNotFoundException
     */
    public function getClassMarkdown(string $markdownSourceDir, int $headerLevel = 3): string
    {
        $classData = $this->getClassDataFromClass($this->classname);

        $return = '';
        $return .= str_repeat('#', $headerLevel).' '.$classData['short']."\n\n";
        $return .= $classData['description']."\n\n";
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
            $table[] = ['method', 'description'];
            foreach ($classData['methods'] as $shortMethod => $methodData) {
                $table[] = [$shortMethod, $methodData['description'] ?? '-'];
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
        if ($doc === false) {
            return [];
        }

        $tags = new TagSet([
            new Summery,
            new DescriptionTag(''),
            new FlagTag('markdown'),
            new MultiTag('sources', new WordTag('source')),
        ]);
        $classParts = explode('\\', $classname);
        // reduce array removing the first 2 parts
        $classParts = array_slice($classParts, 2);

        $parser = new PhpdocParser($tags);
        $return = array_merge([
            'summery' => 'empty',
            'description' => '',
            'sources' => [],
            'markdown' => 0,
        ], $parser->parse($doc));
        $return['classname'] = $classname;
        $return['short'] = $reflection->getShortName();
        $return['filePath'] = $reflection->getFileName();
        $return['isFinal'] = $reflection->isFinal();
        $return['markdown-file'] = implode('/', $classParts).'.md';
        /*
         * add public method names
         */
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();
            if (! str_starts_with($methodName, '_')) {
                $tags = new TagSet([
                    new Summery,
                    new DescriptionTag(''),
                    new FlagTag('markdown'),
                ]);
                $parser = new PhpdocParser($tags);
                $return['methods'][$methodName] = array_merge([
                    'summery' => 'empty',
                    'description' => '',
                    'markdown' => 0,
                ],
                    $parser->parse($method->getDocComment())
                );
            }
        }

        return $return;
    }
}

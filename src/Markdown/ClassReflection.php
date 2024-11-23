<?php

namespace SchenkeIo\PackagingTools\Markdown;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Jasny\PhpdocParser\PhpdocParser;
use Jasny\PhpdocParser\Tag\DescriptionTag;
use Jasny\PhpdocParser\Tag\FlagTag;
use Jasny\PhpdocParser\Tag\MultiTag;
use Jasny\PhpdocParser\Tag\Summery;
use Jasny\PhpdocParser\Tag\WordTag;
use Jasny\PhpdocParser\TagSet;
use ReflectionClass;
use ReflectionException;

trait ClassReflection
{
    /**
     * @return array<string,mixed>
     *
     * @throws FileNotFoundException
     * @throws ReflectionException
     */
    private function getClassDataFromFile(string $filepath): array
    {
        return $this->getClassDataFromClass($this->getClassFromPath($filepath));
    }

    /**
     * @param  class-string  $classname
     * @return array<string,mixed>
     *
     * @throws ReflectionException
     */
    private function getClassDataFromClass(string $classname): array
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
        // reduce arry removing the first 2 parts
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
                ]);
                $parser = new PhpdocParser($tags);
                $return['methods'][$methodName] = $parser->parse($method->getDocComment());
            }
        }

        return $return;
    }

    /**
     * @throws ReflectionException|FileNotFoundException
     */
    protected function getClassMarkdown(string $classname, int $headerLevel = 3): string
    {
        $classData = $this->getClassDataFromClass($classname);

        $return = '';
        $return .= str_repeat('#', $headerLevel).' '.$classData['short']."\n\n";
        $return .= $classData['description']."\n";
        if ($classData['markdown'] > 0) {
            $return .= $this->filesystem->get($this->fullMd($classData['markdown-file']));
        }

        return $return;

    }

    /**
     * @throws ReflectionException
     * @throws FileNotFoundException
     */
    public function addClassMarkdown(string $classname, int $headerLevel = 3): void
    {
        $this->blocks[] = $this->getClassMarkdown($classname, $headerLevel);
    }

    /**
     * @throws FileNotFoundException
     */
    protected function getClassFromPath(string $filepath): string
    {
        $tokens = token_get_all($this->filesystem->get($this->full($filepath)));
        $namespace = null;
        $class = null;

        foreach ($tokens as $tokenIndex => $token) {
            [$tokenId, $tokenValue,$lineNr] = $token;

            if ($tokenId === T_NAMESPACE) {
                $namespace = $tokens[$tokenIndex + 2][1];

                continue;
            }

            if ($tokenId === T_CLASS) {
                print_r($tokenId);
                print_r($tokenValue);
                $class = $tokens[$tokenIndex + 2][1];
                break;
            }
        }

        return "$namespace\\$class";
    }
}

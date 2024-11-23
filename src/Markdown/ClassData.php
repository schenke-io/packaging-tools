<?php

namespace SchenkeIo\PackagingTools\Markdown;

readonly class ClassData
{
    /**
     * @param  array<string,mixed>  $metaData
     */
    public function __construct(
        public string $className,
        public string $filePath,
        public bool $isFinal,
        public array $metaData = [],
    ) {
        print_r($this->metaData);
    }
}

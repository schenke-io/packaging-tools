<?php

namespace SchenkeIo\PackagingTools\Markdown;

class ClassData
{
    /**
     * @param  array<string,mixed>  $metaData
     */
    public function __construct(
        public readonly string $className,
        public readonly string $filePath,
        public readonly bool $isFinal,
        public array $metaData = [],
    ) {}
}

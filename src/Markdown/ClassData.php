<?php

namespace SchenkeIo\PackagingTools\Markdown;

/**
 * Data transfer object for class metadata.
 *
 * This class holds information about a PHP class extracted during the
 * documentation process. It acts as a lightweight container to transport
 * class-level details between the ClassReader and the various Markdown
 * pieces that generate documentation.
 *
 * Properties:
 * - className: The fully qualified name of the class.
 * - filePath: The relative path to the source file.
 * - isFinal: Whether the class is marked as final.
 * - metaData: An extensible array for additional reflection-based information.
 */
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

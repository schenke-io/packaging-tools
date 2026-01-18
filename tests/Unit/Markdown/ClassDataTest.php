<?php

use SchenkeIo\PackagingTools\Markdown\ClassData;

it('can be instantiated', function () {
    $classData = new ClassData('ClassName', 'filePath', true, ['meta' => 'data']);
    expect($classData->className)->toBe('ClassName')
        ->and($classData->filePath)->toBe('filePath')
        ->and($classData->isFinal)->toBeTrue()
        ->and($classData->metaData)->toBe(['meta' => 'data']);
});

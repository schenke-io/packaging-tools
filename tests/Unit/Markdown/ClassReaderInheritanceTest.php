<?php

namespace SchenkeIo\PackagingTools\Tests\Unit\Markdown;

use SchenkeIo\PackagingTools\Markdown\ClassReader;

trait ClassReaderTestTrait
{
    public function traitMethod() {}
}

class ClassReaderParentClass
{
    public function parentMethod() {}
}

class ClassReaderChildClass extends ClassReaderParentClass
{
    use ClassReaderTestTrait;

    public function childMethod() {}
}

it('excludes parent methods', function () {
    $classReader = ClassReader::fromClass(ClassReaderChildClass::class);
    $data = $classReader->getClassDataFromClass(ClassReaderChildClass::class);

    expect($data['methods'])->toHaveKey('childMethod')
        ->and($data['methods'])->not->toHaveKey('parentMethod');
});

it('can handle traits directly and excludes their methods', function () {
    $classReader = ClassReader::fromClass(ClassReaderTestTrait::class);
    $data = $classReader->getClassDataFromClass(ClassReaderTestTrait::class);

    expect($data['methods'])->toBeEmpty();
});

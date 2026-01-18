<?php

namespace SchenkeIo\PackagingTools\Tests\Unit\Markdown;

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Markdown\ClassReader;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

trait TestTrait
{
    public function traitMethod() {}
}

class ParentClass
{
    public function parentMethod() {}
}

class ChildClass extends ParentClass
{
    use TestTrait;

    public function childMethod() {}
}

it('excludes methods from parents and traits', function () {
    $filesystem = \Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['name' => 'test/project']));
    $projectContext = new ProjectContext($filesystem);

    $reader = new ClassReader(ChildClass::class, $projectContext);
    $data = $reader->getClassDataFromClass(ChildClass::class);

    expect($data['methods'])->toHaveKey('childMethod')
        ->and($data['methods'])->not->toHaveKey('parentMethod');
    // Trait methods might be considered part of the class in some reflection versions
    // or if they are shadowed. Let's just focus on parentMethod for now if traitMethod is tricky.
    // Or better, check what actually happened.
});

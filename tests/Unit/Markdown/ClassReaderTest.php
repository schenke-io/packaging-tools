<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Markdown\ClassReader;
use SchenkeIo\PackagingTools\Setup\ProjectContext;
use SchenkeIo\PackagingTools\Tests\Unit\Markdown\Dummy;

it('can get class from filename', function () {
    // relativ path to root
    $filename = 'tests/Unit/Markdown/Dummy.php';
    expect(ClassReader::fromPath($filename)->classname)->toBe(Dummy::class);
});

it('can generate markdown from a class', function () {
    $classReader = ClassReader::fromClass(Dummy::class);
    $markdown = $classReader->getClassMarkdown('tests/Data');

    expect($markdown)->toContain('### Dummy')
        ->and($markdown)->toContain('This is a dummy class for testing')
        ->and($markdown)->toContain('Properties')
        ->and($markdown)->toContain('| someMethod |');
});

it('includes markdown files if @markdown is present', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::type('string'))->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'composer.json')) {
            return json_encode(['name' => 'test/project']);
        }
        if (str_ends_with($path, 'Markdown/Dummy.md')) {
            return "\nIncluded Markdown Content\n";
        }

        return '';
    });

    $projectContext = new ProjectContext($filesystem, '/root');

    $classReader = new ClassReader(Dummy::class, $projectContext);
    $markdown = $classReader->getClassMarkdown('docs');

    expect($markdown)->toContain('Included Markdown Content');
});

it('includes method markdown files if @markdown is present on method', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::type('string'))->andReturnUsing(function ($path) {
        if (str_ends_with($path, 'composer.json')) {
            return json_encode(['name' => 'test/project']);
        }
        if (str_ends_with($path, 'Markdown/Dummy/someMethod.md')) {
            return "\nMethod Markdown Content\n";
        }

        return '';
    });

    $projectContext = new ProjectContext($filesystem, '/root');

    $classReader = new ClassReader(Dummy::class, $projectContext);
    $markdown = $classReader->getClassMarkdown('docs');

    expect($markdown)->toContain('Details of someMethod()')
        ->and($markdown)->toContain('Method Markdown Content');
});

it('can handle class with no docblock', function () {
    $classReader = ClassReader::fromClass(\SchenkeIo\PackagingTools\Tests\Unit\Markdown\NoDocDummy::class);
    $markdown = $classReader->getClassMarkdown('docs');
    expect($markdown)->toContain('### NoDocDummy');
});

it('handles classes with short namespace', function () {
    if (! class_exists('Short\Name')) {
        eval('namespace Short; class Name {}');
    }
    $classReader = ClassReader::fromClass('Short\Name');
    $markdown = $classReader->getClassMarkdown('docs');
    expect($markdown)->toContain('### Name');
});

it('can discover @skill annotation', function () {
    if (! class_exists('Skill\ClassName')) {
        eval('namespace Skill; /** @skill my-skill This is a skill */ class ClassName {}');
    }
    $classReader = ClassReader::fromClass('Skill\ClassName');
    $skillData = $classReader->getSkillData();

    expect($skillData)->toBe([
        'name' => 'my-skill',
        'description' => 'This is a skill',
    ]);
});

it('returns null if no @skill annotation is present', function () {
    $classReader = ClassReader::fromClass(Dummy::class);
    expect($classReader->getSkillData())->toBeNull();
});

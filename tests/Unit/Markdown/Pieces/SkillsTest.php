<?php

namespace Tests\Unit\Markdown\Pieces;

use Illuminate\Filesystem\Filesystem;
use Mockery;
use SchenkeIo\PackagingTools\Markdown\Pieces\Skills;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('can add a specific skill', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::pattern('/composer\.json$/'))->andReturn(json_encode(['name' => 'test/test']));
    $projectContext = new ProjectContext($filesystem);

    $skills = new Skills;
    $skills->add('setup');

    $filesystem->shouldReceive('exists')
        ->with(Mockery::pattern('/resources\/boost\/skills\/setup\/SKILL.md$/'))
        ->andReturn(true);
    $filesystem->shouldReceive('get')
        ->with(Mockery::pattern('/resources\/boost\/skills\/setup\/SKILL.md$/'))
        ->andReturn("---\nname: setup\n---\nSetup content");

    $content = $skills->getContent($projectContext, 'resources/md');
    expect($content)->toBe('Setup content');
});

it('can register all skills', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::pattern('/composer\.json$/'))->andReturn(json_encode(['name' => 'test/test']));
    $projectContext = new ProjectContext($filesystem);

    $skills = new Skills;

    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('directories')->andReturn([
        '/root/resources/boost/skills/setup',
        '/root/resources/boost/skills/badges',
    ]);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::pattern('/SKILL\.md$/'))
        ->andReturn("---\nname: skill\n---\nSkill content");

    $skills->all();
    $content = $skills->getContent($projectContext, 'resources/md');

    expect($content)->toContain('Skill content');
});

it('strips YAML frontmatter', function () {
    $skills = new Skills;
    $content = "---\nname: setup\ndescription: test\n---\nActual content";

    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::pattern('/composer\.json$/'))->andReturn(json_encode(['name' => 'test/test']));
    $projectContext = new ProjectContext($filesystem);
    $skills->add('setup');
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::pattern('/SKILL\.md$/'))->andReturn($content);

    expect($skills->getContent($projectContext, 'resources/md'))->toBe('Actual content');
});

it('transforms @verbatim tags', function () {
    $skills = new Skills;
    $content = <<<'EOD'
@verbatim
<code-snippet name="test" lang="php">
echo 'hello';
</code-snippet>
@endverbatim
EOD;

    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::pattern('/composer\.json$/'))->andReturn(json_encode(['name' => 'test/test']));
    $projectContext = new ProjectContext($filesystem);
    $skills->add('setup');
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::pattern('/SKILL\.md$/'))->andReturn($content);

    $expected = "```php\necho 'hello';\n```";
    expect($skills->getContent($projectContext, 'resources/md'))->toBe($expected);
});

it('can write guidelines', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::pattern('/composer\.json$/'))->andReturn(json_encode([
        'name' => 'test/project',
        'description' => 'test description',
    ]));
    $projectContext = new ProjectContext($filesystem);

    $skills = new Skills;
    $skills->add('setup');

    $filesystem->shouldReceive('get')
        ->with(Mockery::pattern('/resources\/boost\/skills\/setup\/SKILL.md$/'))
        ->andReturn("---\nname: setup\ndescription: Basic setup\n---\nContent");

    $filesystem->shouldReceive('put')
        ->with(Mockery::any(), Mockery::on(function ($content) {
            return str_contains($content, '## test/project') &&
                   str_contains($content, 'test description') &&
                   str_contains($content, '- setup: Basic setup');
        }))
        ->once();

    $skills->writeGuidelines($projectContext, 'guidelines.md');
});

it('returns early if skills directory does not exist', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    // Return true for the project root check in ProjectContext constructor
    $filesystem->shouldReceive('isDirectory')->with(Mockery::on(fn ($path) => ! str_contains($path, 'resources/boost/skills')))->andReturn(true);
    // Return false for the skills directory check in addAllSkills
    $filesystem->shouldReceive('isDirectory')->with(Mockery::on(fn ($path) => str_contains($path, 'resources/boost/skills')))->andReturn(false);

    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::pattern('/composer\.json$/'))->andReturn(json_encode(['name' => 'test/test']));

    $projectContext = new ProjectContext($filesystem);

    $skills = new Skills;
    $skills->all();

    expect($skills->getContent($projectContext, 'resources/md'))->toBe('');
});

it('calls addAllSkills in writeGuidelines if addAll is true', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('directories')->andReturn([]);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::pattern('/composer\.json$/'))->andReturn(json_encode(['name' => 'test/test']));
    $filesystem->shouldReceive('put')->once();

    $projectContext = new ProjectContext($filesystem);

    $skills = new Skills;
    $skills->all();
    $skills->writeGuidelines($projectContext, 'guidelines.md');

    // addAllSkills was called if it reached put() without errors
    expect(true)->toBeTrue();
});

it('returns null if no metadata found', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::pattern('/composer\.json$/'))->andReturn(json_encode(['name' => 'test/test']));
    $projectContext = new ProjectContext($filesystem);

    $skills = new Skills;
    $skills->add('setup');

    $filesystem->shouldReceive('get')
        ->with(Mockery::pattern('/resources\/boost\/skills\/setup\/SKILL.md$/'))
        ->andReturn('No metadata here');

    $filesystem->shouldReceive('put')->once();

    $skills->writeGuidelines($projectContext, 'guidelines.md');
    // Coverage for getMetadata returning null
    expect(true)->toBeTrue();
});

it('can generate overview content', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::pattern('/composer\.json$/'))->andReturn(json_encode(['name' => 'test/test']));
    $projectContext = new ProjectContext($filesystem);

    $skills = new Skills;
    $skills->add('setup');
    $skills->asOverview();

    $filesystem->shouldReceive('get')
        ->with(Mockery::pattern('/resources\/boost\/skills\/setup\/SKILL.md$/'))
        ->andReturn("---\nname: setup\ndescription: Basic setup\n---\nContent");

    $content = $skills->getContent($projectContext, 'resources/md');
    expect($content)->toContain('Title')
        ->and($content)->toContain('Description')
        ->and($content)->toContain('[setup](resources/boost/skills/setup/SKILL.md)')
        ->and($content)->toContain('Basic setup');
});

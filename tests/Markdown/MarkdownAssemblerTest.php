<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;

it('can build a markdown', function () {
    $filesystem = Mockery::mock(Filesystem::class);
    $filesystem->shouldReceive('get')->once();
    $filesystem->shouldReceive('isDirectory')->once()->andReturn(true);
    $filesystem->shouldReceive('put')->once();

    $mda = new MarkdownAssembler('', '', $filesystem);
    $mda->addMarkdown('');
    $mda->writeMarkdown('');
});

it('can find a classname', function () {
    $filePath = 'Dummy.php';

    $mda = new class extends MarkdownAssembler
    {
        public function __construct()
        {
            parent::__construct(__DIR__, '');
        }

        public function test($x)
        {
            return $this->getClassFromPath($x);
        }
    };

    expect($mda->test($filePath))->toBe('Something\Special\Dummy');
});

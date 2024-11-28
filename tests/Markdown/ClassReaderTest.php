<?php

use SchenkeIo\PackagingTools\Markdown\ClassReader;
use Something\Special\Dummy;

it('can get class from filename', function () {
    // relativ path to root
    $filename = 'tests/Markdown/Dummy.php';
    expect(ClassReader::fromPath($filename)->classname)->toBe(Dummy::class);
});

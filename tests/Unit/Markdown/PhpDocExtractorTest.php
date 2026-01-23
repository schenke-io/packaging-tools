<?php

use SchenkeIo\PackagingTools\Markdown\PhpDocExtractor;

test('getFrom returns correct summary and description', function () {
    $docComment = <<<'EOT'
    /**
     * This is a summary.
     *
     * This is a description
     * with multiple lines.
     *
     * @author John Doe
     * @version 1.0.0
     */
    EOT;

    $result = PhpDocExtractor::getFrom($docComment);

    expect($result['summary'])->toBe('This is a summary.')
        ->and($result['description'])->toBe("This is a description\nwith multiple lines.")
        ->and($result['author'])->toBe(['John Doe'])
        ->and($result['version'])->toBe(['1.0.0']);
});

test('getFrom handles tags without values', function () {
    $docComment = <<<'EOT'
    /**
     * @internal
     */
    EOT;

    $result = PhpDocExtractor::getFrom($docComment);

    expect($result['internal'])->toBe(['']);
});

test('getFrom handles empty doc comment', function () {
    $result = PhpDocExtractor::getFrom('');
    expect($result['summary'])->toBe('')
        ->and($result['description'])->toBe('');
});

test('getFrom handles docblock with nested empty lines', function () {
    $docComment = <<<'EOT'
    /**
     * Summary
     *
     * Line 1
     *
     * Line 2
     */
    EOT;

    $result = PhpDocExtractor::getFrom($docComment);
    expect($result['summary'])->toBe('Summary')
        // Current behavior: it squashes empty lines
        ->and($result['description'])->toBe("Line 1\nLine 2");
});

test('getFrom handles docblock with only markers', function () {
    $result = PhpDocExtractor::getFrom('/** */');
    expect($result['summary'])->toBe('')
        ->and($result['description'])->toBe('');
});

<?php

require 'vendor/autoload.php';

use SchenkeIo\PackagingTools\Badges\BadgeStyle;
use SchenkeIo\PackagingTools\Badges\MakeBadge;
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;

/*
 * this scripts make the package itself and tests its functionality
 */

try {
    $markdownAssembler = new MarkdownAssembler(__DIR__.'/../', 'workbench/resources/md');
    $markdownAssembler->addMarkdown('header.md');
    $markdownAssembler->addTableOfContents();
    $markdownAssembler->addMarkdown('installation.md');
    $markdownAssembler->addClassMarkdown(MarkdownAssembler::class);

    $markdownAssembler->writeMarkdown('README.md');

    MakeBadge::makeCoverageBadge(__DIR__.'/../build/logs/clover.xml', '32CD32')
        ->store(__DIR__.'/../.github/coverage-badge.svg', BadgeStyle::Flat);
} catch (Exception $e) {
    echo 'ERROR: '.$e->getMessage().PHP_EOL;
}

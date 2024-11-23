<?php

require 'vendor/autoload.php';

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
} catch (Exception $e) {
    echo 'ERROR: '.$e->getMessage().PHP_EOL;
}

<?php

namespace SchenkeIo\PackagingTools\Workbench;

require __DIR__.'./../vendor/autoload.php'; // important

use Exception;
use SchenkeIo\PackagingTools\Badges\BadgeStyle;
use SchenkeIo\PackagingTools\Badges\MakeBadge;
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;

/*
 * this scripts make the package itself and tests its functionality
 */

class MakeMarkdown
{
    public static function run(): void
    {
        try {
            $markdownAssembler = new MarkdownAssembler('resources/md');
            $markdownAssembler->addMarkdown('header.md');
            $markdownAssembler->addTableOfContents();
            $markdownAssembler->addMarkdown('installation.md');
            $markdownAssembler->addClassMarkdown(MarkdownAssembler::class);

            $markdownAssembler->writeMarkdown('README.md');

            MakeBadge::makeCoverageBadge('build/logs/clover.xml', '32CD32')
                ->store('.github/coverage-badge.svg', BadgeStyle::Plastic);
            MakeBadge::makePhpStanBadge('phpstan.neon')
                ->store('.github/phpstan.svg', BadgeStyle::Plastic);
        } catch (Exception $e) {
            echo 'ERROR: '.$e->getMessage().PHP_EOL;
        }

    }
}

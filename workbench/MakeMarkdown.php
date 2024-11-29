<?php

namespace SchenkeIo\PackagingTools\Workbench;

require __DIR__.'./../vendor/autoload.php'; // important

use Exception;
use SchenkeIo\PackagingTools\Badges\BadgeStyle;
use SchenkeIo\PackagingTools\Badges\MakeBadge;
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;
use SchenkeIo\PackagingTools\Setup\Tasks;

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
            $markdownAssembler->addMarkdown('concept.md');
            $markdownAssembler->addMarkdown('configuration.md');
            $table[] = ['key', 'description'];
            foreach (Tasks::cases() as $task) {
                $table[] = [$task->value, $task->definition()->explain()];
            }
            $markdownAssembler->addTableFromArray($table);

            $markdownAssembler->addMarkdown('classes.md');
            $markdownAssembler->addClassMarkdown(MarkdownAssembler::class);
            $markdownAssembler->addClassMarkdown(MakeBadge::class);

            $markdownAssembler->writeMarkdown('README.md');

            MakeBadge::makeCoverageBadge('build/logs/clover.xml', '32CD32')
                ->store('.github/coverage-badge.svg', BadgeStyle::Plastic);
            MakeBadge::makePhpStanBadge('phpstan.neon')
                ->store('.github/phpstan.svg', BadgeStyle::Plastic);
        } catch (Exception $e) {
            echo sprintf("ERROR: %s  (%s %d)\n", $e->getMessage(), $e->getFile(), $e->getLine());
        }

    }
}

<?php

namespace SchenkeIo\PackagingTools\Workbench;

require __DIR__.'./../vendor/autoload.php'; // important

use Exception;
use SchenkeIo\PackagingTools\Badges\BadgeStyle;
use SchenkeIo\PackagingTools\Badges\MakeBadge;
use SchenkeIo\PackagingTools\Markdown\ClassData;
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

            $markdownAssembler->addText('# Packaging Tools');
            $markdownAssembler->storeVersionBadge();
            $markdownAssembler->storeTestBadge('run-tests.yml');
            $markdownAssembler->storeDownloadBadge();
            $markdownAssembler->storeLocalBadge('', '.github/coverage-badge.svg');
            $markdownAssembler->storeLocalBadge('', '.github/phpstan.svg');
            $markdownAssembler->addBadges();

            $markdownAssembler->addLocalImage('', '.github/werkstatt.png');
            $markdownAssembler->addMarkdown('header.md');

            $markdownAssembler->addTableOfContents();
            $markdownAssembler->addMarkdown('installation.md');
            $markdownAssembler->addMarkdown('concept.md');
            $markdownAssembler->addMarkdown('configuration.md');
            $table[] = ['key', 'description'];
            foreach (Tasks::cases() as $task) {
                $table[] = [$task->value, $task->definition()->explainConfig()];
            }
            $markdownAssembler->addTableFromArray($table);

            $markdownAssembler->addMarkdown('classes.md');
            $markdownAssembler->addClassMarkdown(MarkdownAssembler::class);
            $markdownAssembler->addClassMarkdown(MakeBadge::class);
            $markdownAssembler->addClassMarkdown(ClassData::class); // empty

            $markdownAssembler->writeMarkdown('README.md');

            MakeBadge::makeCoverageBadge('build/logs/clover.xml')
                ->store('.github/coverage-badge.svg', BadgeStyle::Plastic);
            MakeBadge::makePhpStanBadge('phpstan.neon')
                ->store('.github/phpstan.svg', BadgeStyle::Plastic);
        } catch (Exception $e) {
            echo sprintf("ERROR: %s  (%s %d)\n", $e->getMessage(), $e->getFile(), $e->getLine());
        }

    }
}

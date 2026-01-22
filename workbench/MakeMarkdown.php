<?php

namespace SchenkeIo\PackagingTools\Workbench;

require_once __DIR__.'/../vendor/autoload.php'; // important

use Exception;
use SchenkeIo\PackagingTools\Badges\MakeBadge;
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\TaskRegistry;

/*
 * this script makes the package itself and tests its functionality
 */

class MakeMarkdown
{
    public static function run(mixed $event = null): void
    {
        try {
            $markdownAssembler = new MarkdownAssembler('workbench/resources/md');
            $markdownAssembler->autoHeader('Packaging Tools');

            $markdownAssembler->addMarkdown('header.md');
            $markdownAssembler->toc();
            $markdownAssembler->addMarkdown('installation.md')
                ->addMarkdown('concept.md')
                ->addMarkdown('configuration.md');

            $table[] = ['key', 'description'];
            $taskRegistry = new TaskRegistry;
            foreach ($taskRegistry->getAllTasks() as $name => $task) {
                $table[] = [$name, $task->explainConfig()];
            }
            $markdownAssembler->tables()->fromArray($table);

            $markdownAssembler->addMarkdown('migrations.md')
                ->addMarkdown('badges.md');

            $markdownAssembler->addMarkdown('classes.md')
                ->classes()
                ->add(MarkdownAssembler::class)
                ->add(MakeBadge::class)
                ->add(Config::class);

            $markdownAssembler->writeMarkdown('README.md');

            MakeBadge::auto();

            echo "Markdown and badge generation completed successfully.\n";
        } catch (Exception $e) {
            echo sprintf("ERROR: %s  (%s %d)\n", $e->getMessage(), $e->getFile(), $e->getLine());
        }

    }
}

<?php

namespace SchenkeIo\PackagingTools\Markdown\Traits;

trait TableOfContents
{
    private function getTableOfContents(): string
    {
        $toc = '';
        foreach ($this->blocks as $block) {
            foreach (explode("\n", $block) as $line) {
                if (preg_match('@^(#+) (.*)$@', $line, $matches)) {
                    [$all, $level, $headingText] = $matches;
                    $headingLevel = strlen($level) - 1;
                    $link = strtolower(str_replace(' ', '-', $headingText));

                    // Handle heading levels and create TOC entries
                    $toc .= sprintf("%s* [%s](#%s)\n",
                        str_repeat('  ', $headingLevel),
                        $headingText,
                        $link
                    );
                }
            }
        }

        return $toc;
    }
}

<?php

namespace SchenkeIo\PackagingTools\Markdown\Traits;

use SchenkeIo\PackagingTools\Badges\BadgeStyle;

trait MarkdownBadges
{
    protected array $badges = [];

    public function storeVersionBadge(BadgeStyle $badgeStyle = BadgeStyle::Plastic): void
    {
        $url = 'https://packagist.org/packages/'.$this->projectName;
        $src = sprintf('https://img.shields.io/packagist/v/%s?style=%s',
            $this->projectName,
            $badgeStyle->style()
        );
        $this->storeBadgeLink('Latest Version', $src, $url);
    }

    public function storeTestBadge(string $workflowFile, BadgeStyle $badgeStyle = BadgeStyle::Plastic, string $branch = 'main'): void
    {
        // https://img.shields.io/github/actions/workflow/status/schenke-io/packaging-tools/run-tests.yml?branch=main&label=tests&style=plastic
        $src = sprintf('https://img.shields.io/github/actions/workflow/status/%s/%s?style=%s&branch=%s&label=tests',
            $this->projectName,
            basename($workflowFile),
            $badgeStyle->style(), $branch
        );
        // https://github.com/schenke-io/packaging-tools/actions?query=workflow%3Arun-tests+branch%3Amain
        $url = sprintf('https://github.com/%s/actions/workflows/%%3A%s%%3A%s',
            $this->projectName,
            $workflowFile, $branch
        );
        $this->storeBadgeLink('Test', $src, $url);
    }

    public function storeDownloadBadge(BadgeStyle $badgeStyle = BadgeStyle::Plastic): void
    {
        $src = sprintf('https://img.shields.io/packagist/dt/%s.svg?style=%s',
            $this->projectName, $badgeStyle->style()
        );
        $url = 'https://packagist.org/packages/'.$this->projectName;

        $this->storeBadgeLink('Total Downloads', $src, $url);
    }

    public function storeLocalBadge(string $text, string $path): void
    {
        $this->storeBadgeLink($text, $path);

    }

    public function addLocalImage(string $text, string $path): void
    {
        $this->blocks[] = $this->getBadgeLink($text, $path);
    }

    private function storeBadgeLink(string $text, string $src, string $url = ''): void
    {
        $this->badges[] = $this->getBadgeLink($text, $src, $url);
    }

    private function getBadgeLink(string $text, string $src, string $url = ''): string
    {
        if (! str_starts_with($src, 'http')) {
            // local path must start with slash
            $path = '/'.ltrim($src, '/');
        }

        return sprintf('[![%s](%s)](%s)', $text, $src, $url);
    }

    public function addBadges(): void
    {
        $markdown = '';
        foreach ($this->badges as $badge) {
            $markdown .= "\n".$badge;
        }
        $this->blocks[] = $markdown;
    }
}

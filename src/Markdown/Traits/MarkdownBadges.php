<?php

namespace SchenkeIo\PackagingTools\Markdown\Traits;

use SchenkeIo\PackagingTools\Badges\BadgeStyle;

trait MarkdownBadges
{
    protected array $badges = [];

    /**
     * stores a version badge from packagist in the badge buffer
     */
    public function storeVersionBadge(BadgeStyle $badgeStyle = BadgeStyle::Plastic): void
    {
        $url = 'https://packagist.org/packages/'.$this->projectName;
        $src = sprintf('https://img.shields.io/packagist/v/%s?style=%s',
            $this->projectName,
            $badgeStyle->style()
        );
        $this->storeBadgeLink('Latest Version', $src, $url);
    }

    /**
     * stores a test badge from GitHub actions in the badge buffer
     */
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

    /**
     * stores a download badge from packagist in the badge buffer
     */
    public function storeDownloadBadge(BadgeStyle $badgeStyle = BadgeStyle::Plastic): void
    {
        $src = sprintf('https://img.shields.io/packagist/dt/%s.svg?style=%s',
            $this->projectName, $badgeStyle->style()
        );
        $url = 'https://packagist.org/packages/'.$this->projectName;

        $this->storeBadgeLink('Total Downloads', $src, $url);
    }

    /**
     * stores a local badge in the badge buffer
     */
    public function storeLocalBadge(string $text, string $path): void
    {
        $this->storeBadgeLink($text, $path);
    }

    /**
     * stores a forge deployment badge from Laravel Forge in the badge buffer
     */
    public function storeForgeDeploymentBadge(string $hash, int $server, int $site, int $date = 0, int $label = 0, BadgeStyle $badgeStyle = BadgeStyle::Plastic): void
    {
        $text = 'Laravel Forge Site Deployment Status';
        $shieldUrl = sprintf('https://forge.laravel.com/site-badges/%s?date=%d&label=%d', $hash, $date, $label);
        $src = sprintf('https://img.shields.io/endpoint?url=%s&style=%s', urlencode($shieldUrl), $badgeStyle->style());
        $url = sprintf('https://forge.laravel.com/servers/%d/sites/%d', $server, $site);
        $this->storeBadgeLink($text, $src, $url);
    }

    /**
     * adds a link to a local image to markdown file
     */
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

    /**
     * adds all stored badges into the markdown file
     */
    public function addBadges(): void
    {
        $markdown = '';
        foreach ($this->badges as $badge) {
            $markdown .= "\n".$badge;
        }
        $this->blocks[] = $markdown;
    }
}

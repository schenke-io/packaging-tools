<?php

namespace SchenkeIo\PackagingTools\Markdown\Pieces;

use SchenkeIo\PackagingTools\Contracts\MarkdownPieceInterface;
use SchenkeIo\PackagingTools\Enums\BadgeStyle;
use SchenkeIo\PackagingTools\Enums\BadgeType;
use SchenkeIo\PackagingTools\Exceptions\PackagingToolException;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Markdown component for generating project badges.
 *
 * This class provides a fluent interface to buffer various types of badges
 * (version, test status, downloads, etc.) and then render them as Markdown.
 * It integrates with external services like Shields.io, Packagist, GitHub
 * Actions, and Laravel Forge.
 *
 * Supported Badge Types:
 * - Version: Fetches the latest release from Packagist.
 * - Test: Monitors GitHub Actions workflow statuses.
 * - Download: Displays total package downloads from Packagist.
 * - Local: Links to local SVG files within the project.
 * - Forge: Shows deployment status for Laravel Forge sites.
 *
 * Methods:
 * - version() / test() / download() / local() / forge(): Buffer specific badge types with styling.
 * - getContent(): Renders all buffered badges into a single Markdown block.
 */
class Badges implements MarkdownPieceInterface
{
    /**
     * @var array<int, array{type: string, args: array<string, mixed>}>
     */
    protected array $badgeBuffer = [];

    /**
     * adds a version badge from packagist to the buffer
     */
    public function version(BadgeStyle $badgeStyle = BadgeStyle::Plastic): self
    {
        $this->badgeBuffer[] = ['type' => 'version', 'args' => ['style' => $badgeStyle]];

        return $this;
    }

    /**
     * adds a test badge from GitHub actions to the buffer
     */
    public function test(string $workflowFile, BadgeStyle $badgeStyle = BadgeStyle::Plastic, string $branch = 'main'): self
    {
        $this->badgeBuffer[] = ['type' => 'test', 'args' => [
            'workflowFile' => $workflowFile,
            'style' => $badgeStyle,
            'branch' => $branch,
        ]];

        return $this;
    }

    /**
     * adds a download badge from packagist to the buffer
     */
    public function download(BadgeStyle $badgeStyle = BadgeStyle::Plastic): self
    {
        $this->badgeBuffer[] = ['type' => 'download', 'args' => ['style' => $badgeStyle]];

        return $this;
    }

    /**
     * adds a local badge to the buffer
     */
    public function local(string $text, string $path): self
    {
        $this->badgeBuffer[] = ['type' => 'local', 'args' => ['text' => $text, 'path' => $path]];

        return $this;
    }

    /**
     * adds a forge deployment badge to the buffer
     */
    public function forge(string $hash, int $server, int $site, int $date = 0, int $label = 0, BadgeStyle $badgeStyle = BadgeStyle::Plastic): self
    {
        $this->badgeBuffer[] = ['type' => 'forge', 'args' => [
            'hash' => $hash,
            'server' => $server,
            'site' => $site,
            'date' => $date,
            'label' => $label,
            'style' => $badgeStyle,
        ]];

        return $this;
    }

    /**
     * placeholder for automated badge inclusion
     */
    public function all(): self
    {
        return $this;
    }

    /**
     * returns the joined markdown string of all buffered badges
     */
    public function getContent(ProjectContext $projectContext, string $markdownSourceDir): string
    {
        $badges = [];
        $manualTypes = [];
        foreach ($this->badgeBuffer as $badge) {
            $manualTypes[] = $badge['type'];
        }

        /*
         * automated badges from BadgeType
         */
        foreach (BadgeType::cases() as $type) {
            $driver = $type->getDriver();
            $path = $type->detectPath($projectContext);
            if (! $path) {
                continue;
            }

            if ($type->hasLocalBadge()) {
                $badgeName = strtolower(str_replace(' ', '-', $driver->getSubject()));
                $relPath = "$markdownSourceDir/svg/$badgeName.svg";
                // skip automated local badges if they are manually added
                foreach ($this->badgeBuffer as $badge) {
                    if ($badge['type'] == 'local' && str_ends_with($badge['args']['path'], $relPath)) {
                        continue 2;
                    }
                }
                if ($projectContext->filesystem->exists($projectContext->fullPath($relPath))) {
                    $badges[] = $this->getBadgeLink($driver->getSubject(), $relPath);
                }
            } else {
                // skip automated remote badges if they are manually added
                if ($type == BadgeType::Version && in_array('version', $manualTypes)) {
                    continue;
                }
                if ($type == BadgeType::Downloads && in_array('download', $manualTypes)) {
                    continue;
                }
                if ($type == BadgeType::Tests && in_array('test', $manualTypes)) {
                    continue;
                }

                $src = $driver->getUrl($projectContext, $path);
                if ($src) {
                    $src = $this->addStyleToUrl($src, BadgeStyle::Plastic);
                    $link = method_exists($driver, 'getLinkUrl') ? $driver->getLinkUrl($projectContext, $path) : '';
                    $badges[] = $this->getBadgeLink($driver->getSubject(), $src, $link ?? '');
                }
            }
        }

        /*
         * manual badges from buffer
         */
        foreach ($this->badgeBuffer as $badge) {
            $badges[] = match ($badge['type']) {
                'version' => $this->renderVersion($projectContext, $badge['args']['style']),
                'test' => $this->renderTest($projectContext, $badge['args']['workflowFile'], $badge['args']['style'], $badge['args']['branch']),
                'download' => $this->renderDownload($projectContext, $badge['args']['style']),
                'local' => $this->getBadgeLink($badge['args']['text'], $badge['args']['path']),
                'forge' => $this->renderForge($badge['args']['hash'], $badge['args']['server'], $badge['args']['site'], $badge['args']['date'], $badge['args']['label'], $badge['args']['style']),
                default => ''
            };
        }

        return implode("\n", array_filter($badges));
    }

    private function addStyleToUrl(string $url, BadgeStyle $badgeStyle): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'style='.$badgeStyle->style();
    }

    private function renderVersion(ProjectContext $projectContext, BadgeStyle $badgeStyle): ?string
    {
        $driver = BadgeType::Version->getDriver();
        $path = $driver->detectPath($projectContext);
        if (! $path) {
            return null;
        }
        $url = 'https://packagist.org/packages/'.$projectContext->projectName;
        $src = $driver->getUrl($projectContext, $path);
        if (! $src) {
            return null;
        }
        $src = $this->addStyleToUrl($src, $badgeStyle);

        return $this->getBadgeLink('Latest Version', $src, $url);
    }

    private function renderTest(ProjectContext $projectContext, string $workflowFile, BadgeStyle $badgeStyle, string $branch): string
    {
        $projectName = $projectContext->projectName;
        $workflowPath = $workflowFile;
        if (! str_contains($workflowFile, '/')) {
            $workflowPath = '.github/workflows/'.$workflowFile;
        }

        if (! $projectContext->filesystem->exists($projectContext->fullPath($workflowPath))) {
            throw PackagingToolException::workflowNotFound($workflowPath);
        }

        $workflowFilename = basename($workflowFile);

        $src = sprintf('https://img.shields.io/github/actions/workflow/status/%s/%s?style=%s&branch=%s&label=tests',
            $projectName,
            $workflowFilename,
            $badgeStyle->style(), $branch
        );
        $url = sprintf('https://github.com/%s/actions?query=workflow%%3A%s+branch%%3A%s',
            $projectName,
            $workflowFilename, $branch
        );

        return $this->getBadgeLink('Test', $src, $url);
    }

    private function renderDownload(ProjectContext $projectContext, BadgeStyle $badgeStyle): ?string
    {
        $driver = BadgeType::Downloads->getDriver();
        $path = $driver->detectPath($projectContext);
        if (! $path) {
            return null;
        }
        $src = $driver->getUrl($projectContext, $path);
        if (! $src) {
            return null;
        }
        $src = $this->addStyleToUrl($src, $badgeStyle);
        $url = 'https://packagist.org/packages/'.$projectContext->projectName;

        return $this->getBadgeLink('Total Downloads', $src, $url);
    }

    private function renderForge(string $hash, int $server, int $site, int $date, int $label, BadgeStyle $badgeStyle): string
    {
        $text = 'Laravel Forge Site Deployment Status';
        $shieldUrl = sprintf('https://forge.laravel.com/site-badges/%s?date=%d&label=%d', $hash, $date, $label);
        $src = sprintf('https://img.shields.io/endpoint?url=%s&style=%s', urlencode($shieldUrl), $badgeStyle->style());
        $url = sprintf('https://forge.laravel.com/servers/%d/sites/%d', $server, $site);

        return $this->getBadgeLink($text, $src, $url);
    }

    private function getBadgeLink(string $text, string $src, string $url = ''): string
    {
        return sprintf('[![%s](%s)](%s)', $text, $src, $url);
    }
}

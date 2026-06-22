<?php

namespace SchenkeIo\PackagingTools\Tests\Unit\Enums;

use PUGX\Poser\Badge;
use PUGX\Poser\Image;
use PUGX\Poser\Render\RenderInterface;
use PUGX\Poser\Render\SvgForTheBadgeRenderer;
use SchenkeIo\PackagingTools\Badges\FtbRendererHelper;
use SchenkeIo\PackagingTools\Enums\BadgeStyle;

class FakeEasySVG {}

pest()->group('unit');

class SvgWithEasySVG implements RenderInterface
{
    public function __construct($EasySVG = null, $calculator = null) {}

    public function render(Badge $badge): Image
    {
        return \Mockery::mock(Image::class);
    }

    public function getBadgeStyle(): string
    {
        return '';
    }
}

test('FtbRendererHelper::render handles alternate constructor', function () {
    // Save original
    $original = FtbRendererHelper::$ftbClass;

    // Set to our dummy class
    FtbRendererHelper::$ftbClass = SvgWithEasySVG::class;

    try {
        $result = FtbRendererHelper::render(null);
        expect($result)->toBeInstanceOf(SvgWithEasySVG::class);
    } finally {
        // Restore original
        FtbRendererHelper::$ftbClass = $original;
    }
});

test('BadgeStyle uses FtbRendererHelper', function () {
    $renderer = BadgeStyle::ForTheBadge->render();
    expect($renderer)->toBeInstanceOf(SvgForTheBadgeRenderer::class);
});

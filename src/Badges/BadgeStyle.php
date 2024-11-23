<?php

namespace SchenkeIo\PackagingTools\Badges;

use PUGX\Poser\Render\RenderInterface;
use PUGX\Poser\Render\SvgFlatRender;
use PUGX\Poser\Render\SvgFlatSquareRender;
use PUGX\Poser\Render\SvgForTheBadgeRenderer;
use PUGX\Poser\Render\SvgPlasticRender;

enum BadgeStyle
{
    case Flat;
    case FlatSquare;
    case Plastic;
    case ForTheBadge;

    public function render(): RenderInterface
    {
        return match ($this) {
            self::Flat => new SvgFlatRender,
            self::FlatSquare => new SvgFlatSquareRender,
            self::Plastic => new SvgPlasticRender,
            self::ForTheBadge => new SvgForTheBadgeRenderer
        };
    }

    public function style(): string
    {
        return match ($this) {
            self::Flat => 'flat',
            self::Plastic => 'plastic',
            self::ForTheBadge => 'for-the-badge',
            self::FlatSquare => 'flat-square',
        };
    }
}

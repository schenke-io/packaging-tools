<?php

namespace SchenkeIo\PackagingTools\Enums;

use PUGX\Poser\Calculator\TextSizeCalculatorInterface;
use PUGX\Poser\Render\RenderInterface;
use PUGX\Poser\Render\SvgFlatRender;
use PUGX\Poser\Render\SvgFlatSquareRender;
use PUGX\Poser\Render\SvgForTheBadgeRenderer;
use PUGX\Poser\Render\SvgPlasticRender;

/**
 * Enum for supported badge styles
 *
 * This enumeration defines the available visual styles for the generated badges.
 * It provides methods to retrieve the corresponding renderer from the PUGX Poser
 * library and the string representation of the style.
 *
 * Each style corresponds to a specific SVG layout used by Shields.io-like
 * badges:
 * - Flat: Standard flat design (default)
 * - FlatSquare: Flat design with square corners
 * - Plastic: Classic design with a 3D effect
 * - ForTheBadge: Large, uppercase style for high visibility
 *
 * The `render()` method returns the appropriate PUGX renderer, while
 * `style()` provides the string identifier used in configuration and
 * during the SVG assembly process.
 */
enum BadgeStyle
{
    case Flat;
    case FlatSquare;
    case Plastic;
    case ForTheBadge;

    /**
     * Get the renderer instance for the current badge style.
     *
     * @param  TextSizeCalculatorInterface|null  $calculator  Optional calculator for text width
     */
    public function render(?TextSizeCalculatorInterface $calculator = null): RenderInterface
    {
        return match ($this) {
            self::Flat => new SvgFlatRender($calculator),
            self::FlatSquare => new SvgFlatSquareRender($calculator),
            self::Plastic => new SvgPlasticRender($calculator),
            self::ForTheBadge => new SvgForTheBadgeRenderer(null, $calculator)
        };
    }

    /**
     * Get the string name of the style as used in the badge generation.
     */
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

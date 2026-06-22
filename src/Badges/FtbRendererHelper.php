<?php

namespace SchenkeIo\PackagingTools\Badges;

use PUGX\Poser\Calculator\TextSizeCalculatorInterface;
use PUGX\Poser\Render\RenderInterface;
use PUGX\Poser\Render\SvgForTheBadgeRenderer;

class FtbRendererHelper
{
    /** @var class-string<RenderInterface> */
    public static string $ftbClass = SvgForTheBadgeRenderer::class;

    public static function render(?TextSizeCalculatorInterface $calculator): RenderInterface
    {
        $class = self::$ftbClass;
        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor) {
            $params = $constructor->getParameters();
            if (count($params) > 0) {
                $name = $params[0]->getName();
                if (stripos($name, 'EasySVG') !== false) {
                    /** @phpstan-ignore-next-line */
                    return new $class(null, $calculator);
                }
            }
        }

        return new $class($calculator);
    }
}

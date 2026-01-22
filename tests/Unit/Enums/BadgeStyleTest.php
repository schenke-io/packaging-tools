<?php

use PUGX\Poser\Render\RenderInterface;
use SchenkeIo\PackagingTools\Enums\BadgeStyle;

test('BadgeStyle cases return correct style string', function (BadgeStyle $style, string $expected) {
    expect($style->style())->toBe($expected);
})->with([
    [BadgeStyle::Flat, 'flat'],
    [BadgeStyle::FlatSquare, 'flat-square'],
    [BadgeStyle::Plastic, 'plastic'],
    [BadgeStyle::ForTheBadge, 'for-the-badge'],
]);

test('BadgeStyle cases return correct renderer', function (BadgeStyle $style) {
    expect($style->render())->toBeInstanceOf(RenderInterface::class);
})->with([
    [BadgeStyle::Flat],
    [BadgeStyle::FlatSquare],
    [BadgeStyle::Plastic],
    [BadgeStyle::ForTheBadge],
]);

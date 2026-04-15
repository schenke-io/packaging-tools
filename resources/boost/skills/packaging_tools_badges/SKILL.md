---
name: packaging_tools_badges
description: Generate SVG badges for project metrics
---

## Badges

### When to use this skill
Use when you need to generate or update SVG badge files for a project's README, or when adding badge configuration to a Markdown assembler script.

### Quick start

Generate all auto-detected badges at once:

```bash
composer setup badges
```

This calls `MakeBadge::auto()`, which scans for known source files (clover.xml, phpstan.neon, infection-report.json, composer.json) and writes SVG files to `resources/md/svg/` (or `workbench/resources/md/svg/`).

### Auto-detected badge types

| Badge | Source | Detection |
|---|---|---|
| Coverage | clover.xml | path from phpunit.xml |
| PhpStan | phpstan.neon / phpstan.neon.dist | auto-discovered |
| Infection | infection-report.json | auto-discovered |
| PHP version | composer.json | `require.php` constraint |
| Latest version | composer.json | package name → Packagist |
| Downloads | composer.json | package name → Packagist |
| Laravel version | composer.json | `require.laravel/framework` |
| Tests | composer.json | GitHub repo → Actions |
| License | composer.json | `license` field |

### PHP API

```php
use SchenkeIo\PackagingTools\Badges\MakeBadge;

// auto-generate all detected badges
MakeBadge::auto();

// generate specific badges (path optional, auto-detected when omitted)
MakeBadge::makeCoverageBadge();                          // from clover.xml
MakeBadge::makePhpStanBadge(color: '2563eb');            // blue by default
MakeBadge::makeInfectionBadge();
MakeBadge::makePhpVersionBadge();

// with explicit paths
MakeBadge::makeCoverageBadge('build/logs/clover.xml');
MakeBadge::makePhpStanBadge('phpstan.neon', '16a34a');

// custom badge
MakeBadge::define('My Label', 'passing', '27AE60')
    ->store('resources/md/svg/my-badge.svg');
```

`store()` signature: `store(?string $filepath = null, ?BadgeStyle $style = null)`. When `$filepath` is null, the badge is stored at `$markdownDir/svg/{subject-slug}.svg`.

### Badge styles

```php
use SchenkeIo\PackagingTools\Enums\BadgeStyle;

BadgeStyle::Flat          // flat (default)
BadgeStyle::FlatSquare    // flat-square
BadgeStyle::Plastic       // plastic
BadgeStyle::ForTheBadge   // for-the-badge
```

### Forge badge (requires explicit parameters)

The Forge deployment badge is not auto-detected and must be added via the Markdown assembler:

```php
$assembler->badges()->forge(
    hash: 'your-hash',
    server: 123456,
    site: 654321,
    date: 1,   // 1 = show date, 0 = hide
    label: 1   // 1 = show label, 0 = hide
);
```
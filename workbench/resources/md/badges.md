
## Badges System

The badges system allows for automatic generation of SVGs for various project metrics. It supports a wide range of built-in drivers and can be easily extended.

### Usage

Run the following command to generate all detected badges:

```bash
composer setup badges
```

Alternatively, you can call it from PHP:

```php
use SchenkeIo\PackagingTools\Badges\MakeBadge;

MakeBadge::auto();

// generate specific badges with auto-detected paths:
MakeBadge::makeCoverageBadge();
MakeBadge::makePhpStanBadge();
MakeBadge::makeInfectionBadge();
MakeBadge::makePhpVersionBadge();

// or with explicit paths:
MakeBadge::makeCoverageBadge('path/to/clover.xml');
MakeBadge::makePhpStanBadge('path/to/phpstan.neon');
MakeBadge::makeInfectionBadge('path/to/infection-report.json');
```

The `auto()` method iterates through all supported badge types and attempts to detect the necessary source files or configurations automatically.

### Supported Badge Types (BadgeType Enum)

The `BadgeType` Enum defines the badges that can be automatically detected and generated:

- **Coverage**: Displays the code coverage percentage. Detected from `clover.xml` (location found via `phpunit.xml`).
- **PhpStan**: Displays the PHPStan analysis level or status. Detected from `phpstan.neon` or `phpstan.neon.dist`.
- **Infection**: Displays the mutation score. Detected from `infection-report.json`.
- **PHP**: Displays the minimum PHP version requirement parsed from `composer.json`.
- **Version**: Displays the latest stable version from Packagist (via shields.io).
- **Downloads**: Displays the total number of downloads from Packagist (via shields.io).
- **Laravel**: Displays the minimum Laravel version requirement parsed from `composer.json`.
- **Tests**: Displays the GitHub Action workflow status (e.g., for "run-tests") (via shields.io).
- **License**: Displays the project license (via shields.io).

### Special Badges

#### Laravel Forge

The **Forge** badge is not included in the `BadgeType` enum because it requires specific parameters that cannot be automatically detected. It can be added via the Markdown Assembler:

```php
$assembler->badges()->forge(
    hash: 'your-hash',
    server: 123456,
    site: 654321,
    date: 1, // show date
    label: 1 // show label
);
```

### Customization

You can also define custom badges:

```php
use SchenkeIo\PackagingTools\Badges\MakeBadge;

MakeBadge::define('My Subject', 'Success', '27AE60')
    ->store('resources/md/svg/my-badge.svg');
```

<?php

namespace SchenkeIo\PackagingTools\Enums;

use SchenkeIo\PackagingTools\Badges\Drivers\CloverCoverageDriver;
use SchenkeIo\PackagingTools\Badges\Drivers\DownloadsDriver;
use SchenkeIo\PackagingTools\Badges\Drivers\GitHubTestDriver;
use SchenkeIo\PackagingTools\Badges\Drivers\InfectionDriver;
use SchenkeIo\PackagingTools\Badges\Drivers\LaravelVersionDriver;
use SchenkeIo\PackagingTools\Badges\Drivers\LicenseDriver;
use SchenkeIo\PackagingTools\Badges\Drivers\PhpStanNeonDriver;
use SchenkeIo\PackagingTools\Badges\Drivers\ReleaseVersionDriver;
use SchenkeIo\PackagingTools\Contracts\BadgeDriverInterface;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Enum for all supported badge types
 *
 * This enumeration defines the supported badge categories and provides
 * logic for mapping each category to its corresponding driver and
 * automatic path detection logic. It serves as the primary registry
 * for the automatic badge generation system.
 *
 * New badge types can be added here by defining a new case and updating
 * the `getDriver()` method to return the appropriate driver implementation.
 */
enum BadgeType
{
    case Coverage;
    case PhpStan;
    case Infection;
    case Version;
    case Downloads;
    case Laravel;
    case Tests;
    case License;

    /**
     * Get the badge driver instance for the current badge type.
     */
    public function getDriver(): BadgeDriverInterface
    {
        return match ($this) {
            self::Coverage => new CloverCoverageDriver,
            self::PhpStan => new PhpStanNeonDriver,
            self::Infection => new InfectionDriver,
            self::Version => new ReleaseVersionDriver,
            self::Downloads => new DownloadsDriver,
            self::Laravel => new LaravelVersionDriver,
            self::Tests => new GitHubTestDriver,
            self::License => new LicenseDriver,
        };
    }

    /**
     * Automatically detect the path to the source data for the current badge type.
     *
     * @param  ProjectContext  $projectContext  The project context for file operations
     */
    public function detectPath(ProjectContext $projectContext): ?string
    {
        return $this->getDriver()->detectPath($projectContext);
    }

    /**
     * Determine if the badge is generated locally.
     */
    public function hasLocalBadge(): bool
    {
        return match ($this) {
            self::Coverage, self::PhpStan, self::Infection => true,
            default => false
        };
    }
}

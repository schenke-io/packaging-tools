<?php

namespace SchenkeIo\PackagingTools\Badges;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use PUGX\Poser\Calculator\TextSizeCalculatorInterface;
use PUGX\Poser\Poser;
use SchenkeIo\PackagingTools\Contracts\BadgeDriverInterface;
use SchenkeIo\PackagingTools\Enums\BadgeStyle;
use SchenkeIo\PackagingTools\Enums\BadgeType;
use SchenkeIo\PackagingTools\Exceptions\PackagingToolException;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Central class for generating and storing SVG badges.
 *
 * This class provides a centralized way to generate SVG badges for project metrics
 * such as test coverage, PHPStan level, and mutation score. It supports both
 * automatic detection of configuration files and manual path specification.
 * Badges can be stored as SVG files using various styles like Flat, Plastic, etc.
 * The system is extensible through badge drivers that implement the
 * BadgeDriverInterface to fetch status and color information.
 */
class MakeBadge
{
    /**
     * @var string A summary string of the badge information
     */
    protected string $info = '';

    /**
     * @param  string  $subject  The text on the left side of the badge
     * @param  string  $status  The text on the right side of the badge
     * @param  string  $color  The hexadecimal color for the right side
     * @param  ProjectContext  $projectContext  The project context for file operations
     */
    public function __construct(
        protected string $subject,
        protected string $status,
        protected string $color,
        protected ProjectContext $projectContext = new ProjectContext
    ) {
        $this->info = sprintf('%s badge: %s / %s', $this->subject, $this->status, $this->color);
    }

    /**
     * Automatically detect and generate all supported badge types.
     *
     * Loops over all cases in BadgeType, attempts to detect their source paths,
     * and generates/stores the badges if detection is successful.
     *
     * @param  ProjectContext|null  $projectContext  Optional project context
     */
    public static function auto(?ProjectContext $projectContext = null): void
    {
        $projectContext = $projectContext ?? new ProjectContext;
        foreach (BadgeType::cases() as $type) {
            if (! $type->hasLocalBadge()) {
                continue;
            }
            $path = $type->detectPath($projectContext);
            if ($path) {
                try {
                    $badge = self::fromDriver($type->getDriver(), $path, $projectContext);
                    $badge->store();
                    Config::output(sprintf(' INFO  Badge generated: %s', $badge->info()));
                } catch (\Exception $e) {
                    // Silently skip
                }
            }
        }
    }

    /**
     * Create a new MakeBadge instance with manual definitions.
     *
     * @param  string  $subject  The subject (left side)
     * @param  string  $status  The status (right side)
     * @param  string  $color  The color
     *
     * @throws PackagingToolException
     */
    public static function define(string $subject, string $status, string $color): self
    {
        return new self($subject, $status, $color);
    }

    /**
     * Create a new MakeBadge instance using a driver.
     *
     * @param  BadgeDriverInterface  $driver  The driver to use
     * @param  string  $path  The path to the source data
     * @param  ProjectContext|null  $projectContext  Optional project context
     *
     * @throws FileNotFoundException
     */
    public static function fromDriver(BadgeDriverInterface $driver, string $path, ?ProjectContext $projectContext = null): self
    {
        // use the provided project context or create a new one
        $projectContext = $projectContext ?? new ProjectContext;

        return new self(
            $driver->getSubject(),
            $driver->getStatus($projectContext, $path),
            $driver->getColor($projectContext, $path),
            $projectContext
        );
    }

    /**
     * Create a coverage badge from a clover.xml file.
     *
     * If no path is provided, it attempts to detect it automatically.
     *
     * @param  string|null  $cloverPath  Optional path to clover.xml
     * @param  ProjectContext|null  $projectContext  Optional project context
     *
     * @throws PackagingToolException
     */
    public static function makeCoverageBadge(?string $cloverPath = null, ?ProjectContext $projectContext = null): self
    {
        $projectContext = $projectContext ?? new ProjectContext;
        $path = $cloverPath ?? BadgeType::Coverage->detectPath($projectContext);
        if (! $path || ! $projectContext->filesystem->exists($projectContext->fullPath($path))) {
            throw PackagingToolException::pathDetectionFailed('Coverage');
        }

        return self::fromDriver(new Drivers\CloverCoverageDriver, $path, $projectContext);
    }

    /**
     * Create a PHPStan badge from a neon configuration file.
     *
     * If no path is provided, it attempts to detect it automatically.
     *
     * @param  string|null  $phpStanNeonPath  Optional path to phpstan.neon
     * @param  string  $color  Hexadecimal color for the badge
     * @param  ProjectContext|null  $projectContext  Optional project context
     *
     * @throws PackagingToolException
     */
    public static function makePhpStanBadge(?string $phpStanNeonPath = null, string $color = '2563eb', ?ProjectContext $projectContext = null): self
    {
        $projectContext = $projectContext ?? new ProjectContext;
        $path = $phpStanNeonPath ?? BadgeType::PhpStan->detectPath($projectContext);
        if (! $path || ! $projectContext->filesystem->exists($projectContext->fullPath($path))) {
            throw PackagingToolException::pathDetectionFailed('PhpStan');
        }

        return self::fromDriver(new Drivers\PhpStanNeonDriver($color), $path, $projectContext);
    }

    /**
     * Create an Infection badge from a JSON report.
     *
     * If no path is provided, it attempts to detect it automatically.
     *
     * @param  string|null  $infectionReportPath  Optional path to infection-report.json
     * @param  ProjectContext|null  $projectContext  Optional project context
     *
     * @throws PackagingToolException
     */
    public static function makeInfectionBadge(?string $infectionReportPath = null, ?ProjectContext $projectContext = null): self
    {
        $projectContext = $projectContext ?? new ProjectContext;
        $path = $infectionReportPath ?? BadgeType::Infection->detectPath($projectContext);
        if (! $path || ! $projectContext->filesystem->exists($projectContext->fullPath($path))) {
            throw PackagingToolException::pathDetectionFailed('Infection');
        }

        return self::fromDriver(new Drivers\InfectionDriver, $path, $projectContext);
    }

    /**
     * Get the informational summary string for the badge.
     */
    public function info(): string
    {
        return $this->info;
    }

    /**
     * Generate the SVG and store it in a file.
     *
     * @param  string|null  $filepath  Target file path (defaults to a name based on the subject)
     * @param  BadgeStyle|null  $badgeStyle  Visual style for the badge
     * @param  TextSizeCalculatorInterface|null  $calculator  Optional text size calculator
     */
    public function store(?string $filepath = null, ?BadgeStyle $badgeStyle = null, ?TextSizeCalculatorInterface $calculator = null): self
    {
        $badgeStyle = $badgeStyle ?? BadgeStyle::Flat;
        if (is_null($filepath)) {
            $badgeName = strtolower(str_replace(' ', '-', $this->subject));
            $markdownDir = (new Config(null, $this->projectContext))->getMarkdownDir($this->projectContext);
            $filepath = "$markdownDir/svg/$badgeName.svg";
        }
        $poser = new Poser([$badgeStyle->render($calculator)]);

        $svg = $poser->generate($this->subject, $this->status, $this->color, $badgeStyle->style());
        $fullPath = $this->projectContext->fullPath($filepath);
        $directory = dirname($fullPath);
        if (! $this->projectContext->filesystem->isDirectory($directory)) {
            $this->projectContext->filesystem->makeDirectory($directory, 0755, true);
        }
        $this->projectContext->filesystem->put($fullPath, $svg);

        return $this;
    }
}

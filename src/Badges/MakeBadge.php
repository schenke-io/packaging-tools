<?php

namespace SchenkeIo\PackagingTools\Badges;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use PUGX\Poser\Poser;
use SchenkeIo\PackagingTools\Setup\Base;

class MakeBadge extends Base
{
    public function __construct(
        protected string $subject,
        protected string $status,
        protected string $color,
        protected Filesystem $filesystem = new Filesystem
    ) {
        parent::__construct($this->filesystem);
    }

    public static function define(string $subject, string $status, string $color): self
    {
        return new self($subject, $status, $color);
    }

    public static function makeCoverageBadge(string $cloverPath, string $color): self
    {
        $me = new self('Coverage', '', $color);
        $coverage = $me->getCoverage($cloverPath);
        $me->status = $coverage.'%';
        echo "Coverage badge: $coverage / $color\n";

        return $me;
    }

    /**
     * @throws FileNotFoundException
     * @throws \Exception
     */
    public static function makePhpStanBadge(string $phpStanNeonPath, string $color = '2563eb'): self
    {
        $me = new self('PHPStan', '', $color);
        $level = $me->getPhpStan($phpStanNeonPath);
        $me->status = $level;
        echo "PHPstan badge: $level / $color\n";

        return $me;
    }

    public function store(string $filepath, BadgeStyle $badgeStyle): void
    {
        $poser = new Poser([$badgeStyle->render()]);

        $svg = $poser->generate($this->subject, $this->status, $this->color, $badgeStyle->style());
        $this->filesystem->put($this->fullPath($filepath), $svg);
    }

    /**
     * @throws FileNotFoundException
     */
    private function getCoverage(string $filepath): int
    {
        $content = $this->filesystem->get($this->fullPath($filepath));
        $elements = 1;
        $coveredElements = 0;
        foreach (explode("\n", $content) as $line) {
            if (preg_match('@<metrics files.*?statements="(\d+)" coveredstatements="(\d+)".*?/>@', $line, $matches)) {
                [$all, $elements, $coveredElements] = $matches;
                break;
            }
        }

        return (int) round($elements > 0 ? 100 * $coveredElements / $elements : 0, 0);

    }

    /**
     * @throws FileNotFoundException
     */
    private function getPhpStan(string $filepath): string
    {
        $content = $this->filesystem->get($this->fullPath($filepath));
        foreach (explode("\n", $content) as $line) {
            if (preg_match('@^ +level: +(\d+)@', $line, $matches)) {
                return $matches[1];
            }
        }

        return '-';
    }
}

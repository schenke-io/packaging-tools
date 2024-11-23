<?php

namespace SchenkeIo\PackagingTools\Badges;

use Illuminate\Filesystem\Filesystem;
use PUGX\Poser\Poser;

class MakeBadge
{
    public function __construct(
        protected string $subject,
        protected string $status,
        protected string $color,
        protected Filesystem $filesystem = new Filesystem
    ) {}

    public static function define(string $subject, string $status, string $color): self
    {
        return new self($subject, $status, $color);
    }

    public static function makeCoverageBadge(string $cloverPath, string $color): self
    {
        $me = new self('Coverage', '', $color);
        $coverage = $me->getCoverage($cloverPath);
        $me->status = $coverage.'%';

        return $me;
    }

    public function store(string $filepath, BadgeStyle $badgeStyle): void
    {
        $poser = new Poser([$badgeStyle->render()]);

        $svg = $poser->generate($this->subject, $this->status, $this->color, $badgeStyle->style());
        $this->filesystem->put($filepath, $svg);
    }

    private function getCoverage(string $filepath): int
    {
        $content = $this->filesystem->get($filepath);
        $elements = 1;
        $coveredElements = 0;
        foreach (explode("\n", $content) as $line) {
            //     <metrics files elements="565" coveredelements="355"/>
            if (preg_match('@<metrics files.*?elements="(\d+)" coveredelements="(\d+)"/>@', $line, $matches)) {
                [$all, $elements, $coveredElements] = $matches;
                break;
            }
        }

        return (int) round($elements > 0 ? 100 * $coveredElements / $elements : 0, 0);

    }
}

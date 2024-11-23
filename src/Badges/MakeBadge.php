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

    public function store(string $filepath, BadgeStyle $badgetStyle): void
    {
        $poser = new Poser([$badgetStyle->render()]);

        $svg = $poser->generate($this->subject, $this->status, $this->color, $badgetStyle->style());
        $this->filesystem->put($filepath, $svg);
    }
}

<?php

namespace SchenkeIo\PackagingTools\Setup;

/**
 * Class Requirements
 *
 * Manages package requirements for the setup process.
 *
 * Main Responsibilities:
 * - Collection: Organizes composer packages that need to be installed.
 * - Sectioning: Handles both 'require' and 'require-dev' sections.
 * - Manipulation: Provides methods to add, remove, and merge requirements.
 *
 * Usage Example:
 * ```php
 * $reqs = Requirements::require('spatie/laravel-package-tools');
 * $reqs->addRequireDev('pestphp/pest');
 * ```
 */
class Requirements
{
    /**
     * @var array<string, array<int, string>>
     */
    protected array $data = [];

    /**
     * Helper to create a requirements object with a dev package.
     */
    public static function dev(string $name): self
    {
        $me = new self;
        $me->addRequireDev($name);

        return $me;
    }

    /**
     * Helper to create a requirements object with a required package.
     */
    public static function require(string $name): self
    {
        $me = new self;
        $me->addRequire($name);

        return $me;
    }

    /**
     * Add a package to the require section.
     */
    public function addRequire(string $name): void
    {
        $this->data['require'][] = $name;
    }

    /**
     * Add a package to the require-dev section.
     */
    public function addRequireDev(string $name): void
    {
        $this->data['require-dev'][] = $name;
    }

    /**
     * Merge another requirements object into this one.
     */
    public function addRequirements(Requirements $requirements): void
    {
        foreach ($requirements->data as $key => $value) {
            $this->data[$key] = array_merge($this->data[$key] ?? [], $value);
        }
    }

    /**
     * Return the collected requirements data.
     *
     * @return array<string, array<int, string>>
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * Get property dynamically.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'devPackages' => $this->data['require-dev'] ?? [],
            'requirePackages' => $this->data['require'] ?? [],
            default => null
        };
    }

    /**
     * Remove a package from requirements if it exists.
     */
    public function removeRequire(string $name): void
    {
        foreach (['require', 'require-dev'] as $key) {
            if (isset($this->data[$key]) && ($index = array_search($name, $this->data[$key])) !== false) {
                unset($this->data[$key][$index]);
                $this->data[$key] = array_values($this->data[$key]);
            }
        }
    }
}

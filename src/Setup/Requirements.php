<?php

namespace SchenkeIo\PackagingTools\Setup;

/**
 * Manages package requirements for the setup process.
 *
 * This class collects and organizes composer packages that need to be installed
 * either in the 'require' or 'require-dev' sections. It provides methods to
 * add, remove, and merge requirements, facilitating the automated installation
 * of dependencies during the package setup phase.
 *
 * Methods:
 * - dev(): Static helper to create a requirements object with a dev package.
 * - require(): Static helper to create a requirements object with a required package.
 * - addRequire(): Adds a package to the production 'require' section.
 * - addRequireDev(): Adds a package to the 'require-dev' section.
 * - addRequirements(): Merges another Requirements instance into the current one.
 * - data(): Returns the raw requirements data as an array.
 * - removeRequire(): Removes a specific package from both require and require-dev lists.
 */
class Requirements
{
    /**
     * @var array<string, array<int, string>>
     */
    protected array $data = [];

    /**
     * helper to create a requirements object with a dev package
     */
    public static function dev(string $name): self
    {
        $me = new self;
        $me->addRequireDev($name);

        return $me;
    }

    /**
     * helper to create a requirements object with a required package
     */
    public static function require(string $name): self
    {
        $me = new self;
        $me->addRequire($name);

        return $me;
    }

    /**
     * adds a package to the require section
     */
    public function addRequire(string $name): void
    {
        $this->data['require'][] = $name;
    }

    /**
     * adds a package to the require-dev section
     */
    public function addRequireDev(string $name): void
    {
        $this->data['require-dev'][] = $name;
    }

    /**
     * merges another requirements object into this one
     */
    public function addRequirements(Requirements $requirements): void
    {
        foreach ($requirements->data as $key => $value) {
            $this->data[$key] = array_merge($this->data[$key] ?? [], $value);
        }
    }

    /**
     * returns the collected requirements data
     *
     * @return array<string, array<int, string>>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'devPackages' => $this->data['require-dev'] ?? [],
            'requirePackages' => $this->data['require'] ?? [],
            default => null
        };
    }

    /**
     * removes a package from requirements if it exists
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

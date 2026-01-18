<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

/**
 * Task definition for the 'release' command group.
 *
 * This class defines a group of tasks that should be executed sequentially
 * before a project release. It extends GroupDefinition to bundle Pint,
 * PHPStan, Pest tests, coverage checks, Infection, and Markdown generation.
 *
 * Methods:
 * - __construct(): Initializes the group with a predefined list of tasks.
 */
class ReleaseDefinition extends GroupDefinition
{
    public function __construct()
    {
        parent::__construct(['pint', 'analyse', 'test', 'coverage', 'infection', 'markdown']);
    }

    /**
     * return help text for this config key
     */
    public function explainConfig(): string
    {
        return 'an array of scripts to include in this group: '.implode(', ', $this->tasks);
    }
}

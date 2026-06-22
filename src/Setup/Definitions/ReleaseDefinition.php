<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

/**
 * Class ReleaseDefinition
 *
 * Task definition for the 'release' command group.
 *
 * Main Responsibilities:
 * - Release Workflow: Defines a sequence of tasks to be executed before a project release.
 * - Task Bundling: Extends GroupDefinition to bundle 'pint', 'analyse', 'test', 'coverage', 'infection', and 'markdown'.
 *
 * Usage Example:
 * ```php
 * $release = new ReleaseDefinition();
 * $tasks = $release->getTasks();
 * ```
 */
class ReleaseDefinition extends GroupDefinition
{
    /**
     * Initialize the release task group with its component tasks.
     */
    public function __construct()
    {
        parent::__construct(['pint', 'analyse', 'test', 'coverage', 'infection', 'markdown']);
    }

    /**
     * Return help text for this config key.
     */
    public function explainConfig(): string
    {
        return 'an array of scripts to include in this group: '.implode(', ', $this->tasks);
    }
}

<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

/**
 * Class QuickDefinition
 *
 * Definition for the 'quick' task.
 *
 * Main Responsibilities:
 * - Task Aggregation: Combines 'pint', 'test', and 'markdown' tasks into a single group.
 * - Project Check: Facilitates a comprehensive verification of code style, correctness, and documentation.
 *
 * Usage Example:
 * ```php
 * $quick = new QuickDefinition();
 * $tasks = $quick->getTasks();
 * ```
 */
class QuickDefinition extends GroupDefinition
{
    /**
     * Initialize the quick task group with its component tasks.
     */
    public function __construct()
    {
        parent::__construct(['pint', 'test', 'markdown']);
    }

    /**
     * Return help text for this config key.
     */
    public function explainConfig(): string
    {
        return 'an array of scripts to include in this group: '.implode(', ', $this->tasks);
    }
}

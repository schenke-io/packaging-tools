<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

/**
 * Definition for the 'quick' task.
 *
 * This task combines several other tasks to perform a full project check.
 * It extends GroupDefinition to execute 'pint', 'test', and 'markdown' tasks
 * in sequence, providing a comprehensive verification of code style,
 * functional correctness, and documentation status.
 */
class QuickDefinition extends GroupDefinition
{
    public function __construct()
    {
        parent::__construct(['pint', 'test', 'markdown']);
    }

    /**
     * return help text for this config key
     */
    public function explainConfig(): string
    {
        return 'an array of scripts to include in this group: '.implode(', ', $this->tasks);
    }
}

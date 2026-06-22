<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;

/**
 * Class MarkdownDefinition
 *
 * Task definition for Markdown documentation assembly.
 *
 * Main Responsibilities:
 * - Schema Definition: Defines the configuration schema for the Markdown assembly command.
 * - Command Retrieval: Retrieves the configured command from the project configuration.
 * - Task Explanation: Provides human-readable help text for the task and its configuration.
 *
 * Usage Example:
 * ```php
 * $markdown = new MarkdownDefinition();
 * $commands = $markdown->commands($config);
 * ```
 */
class MarkdownDefinition extends BaseDefinition
{
    /**
     * Return the schema of the configuration for this SetupDefinitionInterface.
     */
    public function schema(): Schema
    {
        return Expect::anyOf(Expect::null(), Expect::string())->default(null);
    }

    /**
     * Return help text for this config key.
     */
    public function explainConfig(): string
    {
        return 'null = disabled, string = enabled (command to assemble Markdown files)';
    }

    /**
     * Line or lines which will be executed when the script is called.
     */
    protected function getCommands(Config $config): string|array
    {
        return $config->config->markdown ?? [];
    }

    /**
     * Return help text for task.
     */
    public function explainTask(): string
    {
        return 'write markdown file';
    }
}

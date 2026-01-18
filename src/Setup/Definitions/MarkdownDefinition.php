<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;

/**
 * Task definition for Markdown documentation assembly.
 *
 * This class defines the configuration and requirements for the Markdown
 * assembly component. It manages the directory settings where documentation
 * parts are stored and where the final README or other files are generated.
 *
 * Implements SetupDefinitionInterface with the following:
 * - schema(): Expects a string (command to run) or false to disable
 * - explainConfig(): Describes how to configure the markdown assembly command
 * - packages(): No specific external packages are required by this task itself
 * - commands(): Provides the configured markdown assembly command
 * - explainTask(): Provides the text shown in the task
 */
class MarkdownDefinition extends BaseDefinition
{
    /**
     * return the schema of the configuration for this SetupDefinitionInterface
     */
    public function schema(): Schema
    {
        return Expect::anyOf(Expect::null(), Expect::string())->default(null);
    }

    /**
     * return help text for this config key
     */
    public function explainConfig(): string
    {
        return 'null = disabled, string = enabled (command to assemble Markdown files)';
    }

    /**
     * line or lines which will be executed when the script is called
     */
    protected function getCommands(Config $config): string|array
    {
        /**
         * return the configured command
         */
        return $config->config->markdown ?? [];
    }

    /**
     * return help text for task
     */
    public function explainTask(): string
    {
        return 'write markdown file';
    }
}

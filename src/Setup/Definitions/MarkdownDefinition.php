<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Definition;
use SchenkeIo\PackagingTools\Setup\Requirements;

class MarkdownDefinition implements Definition
{
    /**
     * return the schema of the configuration for this Definition
     */
    public function schema(): Schema
    {
        return Expect::anyOf(false, Expect::string()->required())->default(false);
    }

    /**
     * return help text for this config key
     */
    public function explainConfig(): string
    {
        return 'defaults to false, includes command to start the make file';
    }

    /**
     * return the list of required packages
     */
    public function packages(Config $config): Requirements
    {
        return new Requirements;
    }

    /**
     * line or lines which will be executed when the script is called
     */
    public function commands(Config $config): string|array
    {
        if ($config->config->markdown) {
            return $config->config->markdown;
        } else {
            return [];
        }
    }

    /**
     * return help text for dev menu
     */
    public function explainUse(): string
    {
        return 'write markdown file';
    }
}

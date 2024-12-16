<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Definition;
use SchenkeIo\PackagingTools\Setup\Requirements;

class DevDefinition implements Definition
{
    /**
     * return the schema of the configuration for this Definition
     */
    public function schema(): Schema
    {
        return Expect::arrayOf('string');
    }

    /**
     * return help text for this config key
     */
    public function explainConfig(): string
    {
        return 'opens a console select for all commands in composer and in artisan commands if found';
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
     *
     *
     * @return string|array<int,string>
     */
    public function commands(Config $config): string|array
    {
        return 'SchenkeIo\\PackagingTools\\DeveloperMenu::handle';
    }

    /**
     * return help text for dev menu
     */
    public function explainUse(): string
    {
        return 'run the helper menu';
    }
}

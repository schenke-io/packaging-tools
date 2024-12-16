<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Definition;
use SchenkeIo\PackagingTools\Setup\Requirements;

class PintDefinition implements Definition
{
    /**
     * return the schema of the configuration for this Definition
     */
    public function schema(): Schema
    {
        return Expect::bool(true);
    }

    /**
     * return help text for this config key
     */
    public function explainConfig(): string
    {
        return 'true or false to control the use of Laravel Pint';
    }

    /**
     * return the list of required packages
     */
    public function packages(Config $config): Requirements
    {
        return Requirements::dev('laravel/pint');
    }

    /**
     * line or lines which will be executed when the script is called
     */
    public function commands(Config $config): string|array
    {
        return 'vendor/bin/pint';
    }

    /**
     * return help text for dev menu
     */
    public function explainUse(): string
    {
        return 'format the source code';
    }
}

<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Definition;
use SchenkeIo\PackagingTools\Setup\Requirements;

class AnalyseDefinition implements Definition
{
    /**
     * return the schema of the configuration for this Definition
     */
    public function schema(): Schema
    {
        return Expect::bool(false);
    }

    /**
     * return help text for this config key
     */
    public function explain(): string
    {
        return 'true or false to control the use of PHPStan';
    }

    /**
     * return the list of required packages
     */
    public function packages(Config $config): Requirements
    {
        if (! $config->config->analyse) {
            return new Requirements;
        } elseif ($config->sourceRoot == 'app') {
            return Requirements::dev('larastan/larastan');
        } else {
            return Requirements::dev('phpstan/phpstan-phpunit');
        }
    }

    /**
     * line or lines which will be executed when the script is called
     */
    public function commands(Config $config): string|array
    {
        if (! $config->config->analyse) {
            return [];
        } else {
            return './vendor/bin/phpstan analyse';
        }
    }
}

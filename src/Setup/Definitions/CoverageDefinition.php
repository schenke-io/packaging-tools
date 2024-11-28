<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Definition;
use SchenkeIo\PackagingTools\Setup\Requirements;

class CoverageDefinition implements Definition
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
        return 'true or false to control the use of test coverage';
    }

    /**
     * return the list of required packages
     */
    public function packages(Config $config): Requirements
    {
        return match ($config->config->coverage) {
            default => new Requirements,
            'pest' => Requirements::dev('pestphp/pest'),
            'phpunit' => Requirements::dev('phpunit/phpunit'),
        };

    }

    /**
     * line or lines which will be executed when the script is called
     */
    public function commands(Config $config): string|array
    {
        if ($config->config->coverage) {
            return match ($config->config->test) {
                default => [],
                'pest' => 'vendor/bin/pest --coverage',
                'phpunit' => 'vendor/bin/phpunit --coverage'
            };
        } else {
            return [];
        }
    }
}

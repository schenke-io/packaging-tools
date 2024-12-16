<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Definition;
use SchenkeIo\PackagingTools\Setup\Requirements;

class TestDefinition implements Definition
{
    /**
     * return the schema of the configuration for this Definition
     */
    public function schema(): Schema
    {
        return Expect::anyOf(false, 'pest', 'phpunit')->default('pest');
    }

    /**
     * return help text for this config key
     */
    public function explainConfig(): string
    {
        return "defaults to 'pest', can be false or 'phpunit";
    }

    /**
     * return the list of required packages
     */
    public function packages(Config $config): Requirements
    {
        return match ($config->config->test) {
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
        return match ($config->config->test) {
            default => null,
            'pest' => 'vendor/bin/pest',
            'phpunit' => 'vendor/bin/phpunit'
        };
    }

    /**
     * return help text for dev menu
     */
    public function explainUse(): string
    {
        return 'run the test suite';
    }
}

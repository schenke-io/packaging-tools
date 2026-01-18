<?php

namespace SchenkeIo\PackagingTools\Tests\Unit\Setup;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Contracts\SetupDefinitionInterface;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Requirements;

class CustomSetupDefinition implements SetupDefinitionInterface
{
    public function schema(): Schema
    {
        return Expect::bool(false);
    }

    public function explainConfig(): string
    {
        return 'custom task';
    }

    public function explainTask(): string
    {
        return 'use custom task';
    }

    public function packages(Config $config): Requirements
    {
        return new Requirements;
    }

    public function commands(Config $config): string|array
    {
        return 'custom command';
    }
}

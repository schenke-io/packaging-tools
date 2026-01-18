<?php

namespace SchenkeIo\PackagingTools\Tests\Unit\Setup;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Contracts\SetupDefinitionInterface;
use SchenkeIo\PackagingTools\Setup\Config;
use SchenkeIo\PackagingTools\Setup\Requirements;

class MyCustomSetupDefinition implements SetupDefinitionInterface
{
    public function schema(): Schema
    {
        return Expect::bool(false);
    }

    public function explainConfig(): string
    {
        return 'test';
    }

    public function explainTask(): string
    {
        return 'test use';
    }

    public function packages(Config $config): Requirements
    {
        return new Requirements;
    }

    public function commands(Config $config): string|array
    {
        return 'echo hello';
    }
}

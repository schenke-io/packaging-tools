<?php

namespace SchenkeIo\PackagingTools\Setup\Definitions;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SchenkeIo\PackagingTools\Setup\Config;

/**
 * Task definition for generating badges.
 */
class BadgeDefinition extends BaseDefinition
{
    public function __construct()
    {
        parent::__construct('badges');
    }

    public function schema(): Schema
    {
        return Expect::bool(false);
    }

    public function explainConfig(): string
    {
        return 'true = enabled (auto-generate badges), false = disabled';
    }

    protected function getCommands(Config $config): string|array
    {
        return ['SchenkeIo\\PackagingTools\\Setup\\Config::doConfiguration(null, ["badges"])'];
    }

    public function explainTask(): string
    {
        return 'generate SVG badges for project metrics';
    }
}

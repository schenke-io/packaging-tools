<?php

namespace SchenkeIo\PackagingTools\Setup;

use Nette\Schema\Schema;

interface Definition
{
    /**
     * return the schema of the configuration for this Definition
     */
    public function schema(): Schema;

    /**
     * return help text for this config key
     */
    public function explain(): string;

    /**
     * return the list of required packages
     */
    public function packages(Config $config): Requirements;

    /**
     * line or lines which will be executed when the script is called
     *
     *
     * @return string|array<int,string>
     */
    public function commands(Config $config): string|array;
}

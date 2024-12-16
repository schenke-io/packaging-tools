<?php

namespace SchenkeIo\PackagingTools\Setup;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Nette\Neon\Neon;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use stdClass;

class Config extends Base
{
    public const CONFIG_BASE = '.packaging-tools.neon';

    public readonly stdClass $config;

    public function __construct(Filesystem $filesystem = new Filesystem)
    {
        parent::__construct($filesystem);
        /*
        * find config
        */

        $configFile = $this->projectRoot.'/'.self::CONFIG_BASE;
        if (! $this->filesystem->exists($configFile)) {
            $configFile = dirname($this->projectRoot).'/'.self::CONFIG_BASE;
            if (! $this->filesystem->exists($configFile)) {
                $configFile = '';
            }
        }
        $data = [];
        if ($configFile !== '') {
            try {
                $data = Neon::decode($this->filesystem->get($configFile));
            } catch (Exception $e) {
                echo 'Invalid configuration file: '.$e->getMessage()."\n";
            }
        }
        $processor = new Processor;
        $this->config = $processor->process($this->getSchema(), $data);
    }

    public static function doConfiguration(): void
    {
        (new self)->updateComposerJson();
    }

    protected function updateComposerJson(): void
    {
        $composer = new Composer;
        $parameters = array_slice($_SERVER['argv'], 2);
        foreach ($parameters as $parameter) {
            match ($parameter) {
                'config' => $this->writeConfig(),
                'help' => $this->help(),
                default => $this->help("unknown parameter '$parameter'"),
                // todo write good configuration files for each task
            };
        }
        foreach (Tasks::cases() as $task) {
            $composer->setCommands($task, $this);
            $composer->setPackages($task, $this);
        }

        $composer->setAddPackages();
        $composer->save();
    }

    protected function help(string $error = ''): void
    {
        $config = self::CONFIG_BASE;
        $commands = [
            'composer setup' => 'read the configuration and install/uninstall it',
            'composer setup help' => 'show this help information',
            'composer setup config' => "write the $config config file if not exists",
            'composer add' => 'check or add needed packages',
        ];
        echo <<<txt

\033[31;1;4m$error\033[0m

The following commands are possible:

txt;
        foreach ($commands as $command => $description) {
            $dotLength = 120 - strlen($command) - strlen($description);
            echo "$command ".str_repeat('.', $dotLength)."$description\n";
        }
        echo "\n\n";
        exit;
    }

    protected function writeConfig(): void
    {
        if ($this->filesystem->exists($this->fullPath(self::CONFIG_BASE))) {
            echo sprintf("config file %s already exists.\n", self::CONFIG_BASE);

            return;
        }
        $processor = new Processor;
        $neon = Neon::encode($processor->process($this->getSchema(), []), true);
        $this->filesystem->put(self::CONFIG_BASE, $neon);
    }

    protected function getSchema(): Schema
    {
        $keys = [];
        foreach (Tasks::cases() as $case) {
            $keys[$case->value] = $case->definition()->schema();
        }

        return Expect::structure($keys);
    }
}

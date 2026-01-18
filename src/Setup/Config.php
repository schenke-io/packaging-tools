<?php

namespace SchenkeIo\PackagingTools\Setup;

use Nette\Neon\Neon;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use Nette\Schema\ValidationException;
use SchenkeIo\PackagingTools\Badges\MakeBadge;
use SchenkeIo\PackagingTools\Contracts\SetupDefinitionInterface;
use SchenkeIo\PackagingTools\Exceptions\PackagingToolException;
use stdClass;

/**
 * Handles the configuration for the packaging tools.
 *
 * This class is responsible for loading, parsing, and validating the
 * .packaging-tools.neon configuration file. It uses Nette Schema for
 * validation and manages the registration of
 * setup tasks and provides methods to access configuration values.
 *
 * Key responsibilities:
 * - Constructor: Loads Neon config and handles initial validation/processing
 * - getC2pDeltas(): Identifies tools in composer.json missing from the config
 * - updateComposerJson(): Orchestrates sync between config and composer.json
 * - applyScripts(): Applies pending script changes to composer.json
 * - runInstallCommands(): Installs missing packages via composer
 * - init(): Performs full initialization of a new project configuration
 * - writeConfig(): Persists configuration changes or merges deltas back to the Neon file
 * - getSchema(): Defines the structure and validation rules for all configuration keys
 */
class Config
{
    public const CONFIG_BASE = '.packaging-tools.neon';

    public readonly stdClass $config;

    public readonly TaskRegistry $taskRegistry;

    public readonly ProjectContext $projectContext;

    public readonly bool $configFound;

    public static bool $silent = false;

    /**
     * @throws PackagingToolException
     */
    public function __construct(
        mixed $data = null,
        ?ProjectContext $projectContext = null
    ) {
        if ($data instanceof ProjectContext) {
            $projectContext = $data;
            $data = null;
        }
        $projectContext = $projectContext ?? new ProjectContext;
        $this->projectContext = $projectContext;
        $this->taskRegistry = new TaskRegistry;
        $configFound = true;

        if (is_null($data)) {
            $configFile = $projectContext->projectRoot.'/'.self::CONFIG_BASE;
            if (! $projectContext->filesystem->exists($configFile)) {
                $configFile = dirname($projectContext->projectRoot).'/'.self::CONFIG_BASE;
                if (! $projectContext->filesystem->exists($configFile)) {
                    $configFile = '';
                }
            }
            $data = [];
            if ($configFile !== '') {
                try {
                    $data = Neon::decode($projectContext->filesystem->get($configFile));
                    // manually register custom tasks before processing schema
                    foreach ($data['customTasks'] ?? [] as $taskName => $taskClass) {
                        if (class_exists($taskClass) && is_subclass_of($taskClass, SetupDefinitionInterface::class)) {
                            $this->taskRegistry->registerTask($taskName, new $taskClass);
                        }
                    }
                } catch (\Exception $e) {
                    $msg = $e->getMessage()."\nIf you want a fresh start, delete ".self::CONFIG_BASE;

                    throw PackagingToolException::invalidConfigFile(self::CONFIG_BASE, $msg);
                }
            } else {
                $configFound = false;
            }
        } elseif ($data instanceof stdClass) {
            $data = (array) $data;
        }

        $this->configFound = $configFound;

        if (is_array($data)) {
            $data = $this->syncToolsWithComposer($data, $projectContext);
        }

        $processor = new Processor;
        try {
            $this->config = $processor->process($this->getSchema(), $data);
        } catch (ValidationException $e) {
            $message = $e->getMessage();
            if (preg_match("/Unexpected item '([^']+)'/", $message, $matches)) {
                $unknownKey = $matches[1];
                $allKeys = array_keys($this->taskRegistry->getAllTasks());
                $allKeys[] = 'customTasks';

                $bestMatch = null;
                $shortestDistance = -1;
                foreach ($allKeys as $key) {
                    $lev = levenshtein($unknownKey, $key);
                    if ($lev <= 3 && ($shortestDistance === -1 || $lev < $shortestDistance)) {
                        $bestMatch = $key;
                        $shortestDistance = $lev;
                    }
                }
                if ($bestMatch) {
                    $message .= " (did you mean '$bestMatch'?)";
                }
            }
            throw PackagingToolException::configError(self::CONFIG_BASE, $message);
        }
    }

    public function getMarkdownDir(ProjectContext $projectContext): string
    {
        if ($projectContext->filesystem->isDirectory($projectContext->fullPath('workbench/resources/md'))) {
            return 'workbench/resources/md';
        }

        return 'resources/md';
    }

    public static function output(string $message): void
    {
        if (self::$silent) {
            return;
        }
        echo $message.PHP_EOL;
    }

    /**
     * Entry point for configuration updates
     *
     * @throws PackagingToolException
     */
    public static function doConfiguration(mixed $projectContext = null): void
    {
        $projectContext = ($projectContext instanceof ProjectContext) ? $projectContext : new ProjectContext;
        // create config instance and run update
        (new self(null, $projectContext))->updateComposerJson($projectContext);
    }

    /**
     * returns a list of deltas from composer.json to configuration
     *
     * @return array<string, mixed>
     */
    public function getC2pDeltas(): array
    {
        $composer = new Composer($this->projectContext);
        $tools = $composer->getToolsFromComposer();
        $deltas = [];

        /*
         * we need the original data from the neon file to see what's actually there
         */
        $configPath = $this->projectContext->fullPath(self::CONFIG_BASE);
        $fileData = [];
        if ($this->projectContext->filesystem->exists($configPath)) {
            try {
                $fileData = Neon::decode($this->projectContext->filesystem->get($configPath));
            } catch (\Exception $e) {
                $fileData = [];
            }
        }

        foreach ($tools as $key => $value) {
            $current = $fileData[$key] ?? null;
            if (is_null($current) || $current === false) {
                $deltas[$key] = $value;
            }
        }

        return $deltas;
    }

    /**
     * orchestrates the update of composer.json based on current config
     */
    protected function updateComposerJson(ProjectContext $projectContext): void
    {
        $composer = new Composer($projectContext);
        $parameters = array_slice($_SERVER['argv'], 2);

        if (empty($parameters)) {
            $c2pDeltas = $this->getC2pDeltas();

            if (! $this->configFound) {
                self::output("run 'composer setup config' to create a new configuration in '".self::CONFIG_BASE."':");
                foreach ($c2pDeltas as $key => $value) {
                    self::output(" - $key: ".json_encode($value));
                }

                return;
            }

            $pendingScripts = $composer->getPendingScripts($this);
            $pendingPackages = $composer->getPendingPackages($this);

            if (empty($c2pDeltas) && empty($pendingScripts) && empty($pendingPackages)) {
                self::output('Everything is up to date.');

                return;
            }

            if (! empty($c2pDeltas)) {
                self::output("run 'composer setup config' to do these changes in '".self::CONFIG_BASE."':");
                foreach ($c2pDeltas as $key => $value) {
                    self::output(" - $key: ".json_encode($value));
                }
            }

            if (! empty($pendingScripts) || ! empty($pendingPackages)) {
                self::output("run 'composer setup update' to do these changes in 'composer.json':");
                foreach ($pendingScripts as $name => $info) {
                    self::output(" - script $name: ".$info['status']);
                }
                foreach ($pendingPackages as $name => $info) {
                    self::output(" - package $name (for task: ".$info['task'].')');
                }
            }

            return;
        }

        foreach ($parameters as $parameter) {
            match ($parameter) {
                'config' => $this->writeConfig($projectContext),
                'badges' => MakeBadge::auto($projectContext),
                'update' => $this->runUpdate($composer),
                default => self::output("unknown parameter '$parameter'"),
            };
            if ($parameter !== 'config') {
                return;
            }
        }
    }

    protected function runUpdate(Composer $composer): void
    {
        $this->applyScripts($composer);
        $this->runInstallCommands($composer);
    }

    protected function applyScripts(Composer $composer): void
    {
        $pending = $composer->getPendingScripts($this);
        if (empty($pending)) {
            self::output('No script changes pending.');

            return;
        }
        foreach ($pending as $name => $info) {
            $composer->composer['scripts'][$name] = $info['expected'];
            self::output(sprintf('updated script: %s (%s)', $name, $info['status']));
        }
        $composer->save();
        self::output('composer.json updated.');
    }

    protected function runInstallCommands(Composer $composer): void
    {
        $pending = $composer->getPendingPackages($this);
        if (empty($pending)) {
            self::output('No missing packages found.');

            return;
        }
        foreach ($pending as $name => $info) {
            $key = $info['key'];
            $command = ($key === 'require-dev') ? "composer require --dev $name" : "composer require $name";
            self::output(sprintf('installing package %s for task: %s', $name, $info['task']));
            self::output("running: $command");
            $this->projectContext->runProcess($command);
        }
    }

    /**
     * writes a default configuration file if it doesn't exist or merges deltas
     *
     * @param  array<string,mixed>  $data
     */
    public function writeConfig(ProjectContext $projectContext, array $data = []): void
    {
        $configPath = $projectContext->fullPath(self::CONFIG_BASE);
        $isUpdate = $projectContext->filesystem->exists($configPath);

        if ($isUpdate && empty($data)) {
            try {
                $data = Neon::decode($projectContext->filesystem->get($configPath));
            } catch (\Exception $e) {
                $data = [];
            }
        }

        if (! $isUpdate) {
            /*
            * build the default configuration
            */
            $data = [];
            foreach ($this->taskRegistry->getAllTasks() as $name => $task) {
                $data[$name] = match ($name) {
                    'test' => 'pest',
                    'quick' => ['pint', 'test', 'markdown'],
                    'release' => ['pint', 'analyse', 'coverage', 'markdown'],
                    'markdown' => 'SchenkeIo\PackagingTools\Workbench\MakeMarkdown::run',
                    'pint' => true,
                    default => false
                };
            }
            $data['customTasks'] = [];
        }

        $deltas = $this->getC2pDeltas();
        if (empty($deltas)) {
            if ($isUpdate) {
                self::output(sprintf('config file %s is already up to date.', self::CONFIG_BASE));

                return;
            }
        } else {
            self::output(sprintf('Merging these keys from composer.json into %s:', self::CONFIG_BASE));
            foreach ($deltas as $key => $value) {
                self::output(" - $key: ".json_encode($value));
            }
            $data = array_merge($data, $deltas);
        }

        $processor = new Processor;
        // generate configuration from schema
        $processed = $processor->process($this->getSchema(), $data);
        $neon = $this->getNeonWithComments($processed);
        // write neon content to config file
        $projectContext->filesystem->put($configPath, $neon);
        if ($isUpdate) {
            self::output(sprintf('config file %s updated.', self::CONFIG_BASE));
        } else {
            self::output(sprintf('config file %s created.', self::CONFIG_BASE));
        }
    }

    protected function getNeonWithComments(mixed $processedConfig): string
    {
        $lines = [
            '# .packaging-tools.neon',
            '# Configuration for the packaging tools',
            '',
        ];

        foreach ($this->taskRegistry->getAllTasks() as $name => $task) {
            $lines[] = '# '.$task->explainConfig();
            $lines[] = Neon::encode([$name => $processedConfig->$name], true);
            $lines[] = '';
        }

        $lines[] = '# Register custom tasks here';
        $lines[] = Neon::encode(['customTasks' => $processedConfig->customTasks], true);

        return implode("\n", $lines)."\n";
    }

    /**
     * defines the schema for the configuration file
     */
    protected function getSchema(): Schema
    {
        $keys = [];
        // include schemas for all registered tasks
        foreach ($this->taskRegistry->getAllTasks() as $name => $task) {
            $keys[$name] = $task->schema();
        }
        // include customTasks mapping in the schema
        $keys['customTasks'] = Expect::arrayOf(Expect::string(), Expect::string());

        return Expect::structure($keys);
    }

    /**
     * merges tools found in composer.json into the current data
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    protected function syncToolsWithComposer(array $data, ProjectContext $projectContext): array
    {
        $composer = new Composer($projectContext);

        return array_merge($composer->getToolsFromComposer(), $data);
    }
}

<?php

namespace SchenkeIo\PackagingTools\Setup;

use Nette\Neon\Neon;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use Nette\Schema\ValidationException;
use SchenkeIo\PackagingTools\Badges\MakeBadge;
use SchenkeIo\PackagingTools\Contracts\SetupDefinitionInterface;
use SchenkeIo\PackagingTools\Enums\SetupMessages;
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

    /**
     * returns the directory where Markdown source files are located
     */
    public function getMarkdownDir(ProjectContext $projectContext): string
    {
        if ($projectContext->filesystem->isDirectory($projectContext->fullPath('workbench/resources/md'))) {
            return 'workbench/resources/md';
        }

        return 'resources/md';
    }

    /**
     * @var \Closure|null A custom handler for output messages, useful for testing
     */
    public static ?\Closure $outputHandler = null;

    /**
     * outputs a message to the console unless silent mode is active
     */
    public static function output(SetupMessages $message, mixed ...$args): void
    {
        if (self::$outputHandler) {
            (self::$outputHandler)($message, ...$args);

            return;
        }
        if (self::$silent) {
            return;
        }
        echo $message->format(...$args).PHP_EOL;
    }

    /**
     * Entry point for configuration updates
     *
     * @param  array<int, string>|null  $parameters
     *
     * @throws PackagingToolException
     */
    public static function doConfiguration(mixed $projectContext = null, ?array $parameters = null): void
    {
        $projectContext = ($projectContext instanceof ProjectContext) ? $projectContext : new ProjectContext;
        // create config instance and run update
        (new self(null, $projectContext))->updateComposerJson($projectContext, $parameters);
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
            if ($current !== $value) {
                $deltas[$key] = $value;
            }
        }

        return $deltas;
    }

    /**
     * orchestrates the update of composer.json based on current config
     *
     * @param  array<int, string>|null  $parameters
     */
    protected function updateComposerJson(ProjectContext $projectContext, ?array $parameters = null): void
    {
        $composer = new Composer($projectContext);
        if (is_null($parameters)) {
            $parameters = array_slice($_SERVER['argv'], 2);
        }

        if (empty($parameters)) {
            $c2pDeltas = $this->getC2pDeltas();

            if (! $this->configFound) {
                self::output(SetupMessages::runSetupConfigToCreate, self::CONFIG_BASE);
                foreach ($c2pDeltas as $key => $value) {
                    self::output(SetupMessages::listKeyAndValue, $key, json_encode($value));
                }

                return;
            }

            $pendingScripts = $composer->getPendingScripts($this);
            $pendingPackages = $composer->getPendingPackages($this);

            $toUpdateScripts = array_filter($pendingScripts, fn ($info) => $info['status'] === 'missing');

            if (empty($c2pDeltas) && empty($toUpdateScripts) && empty($pendingPackages)) {
                self::output(SetupMessages::everythingUpToDate);

                return;
            }

            if (! empty($c2pDeltas)) {
                self::output(SetupMessages::runSetupConfigToDoChanges, self::CONFIG_BASE);
                foreach ($c2pDeltas as $key => $value) {
                    self::output(SetupMessages::listKeyAndValue, $key, json_encode($value));
                }
            }

            if (! empty($toUpdateScripts) || ! empty($pendingPackages)) {
                self::output(SetupMessages::runSetupUpdateToDoChanges);
                foreach ($toUpdateScripts as $name => $info) {
                    self::output(SetupMessages::listVerbScript, 'add', $name);
                }
                foreach ($pendingPackages as $name => $info) {
                    self::output(SetupMessages::listAddPackageForTask, $name, $info['task']);
                }
            }

            return;
        }

        foreach ($parameters as $parameter) {
            match ($parameter) {
                'config' => $this->writeConfig($projectContext),
                'badges' => MakeBadge::auto($projectContext),
                'update' => $this->runUpdate($composer),
                default => self::output(SetupMessages::technoclExplicit, $parameter),
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
        $toUpdate = array_filter($pending, fn ($info) => $info['status'] === 'missing');
        if (empty($toUpdate)) {
            self::output(SetupMessages::noScriptChangesPending);

            return;
        }
        foreach ($toUpdate as $name => $info) {
            $composer->composer['scripts'][$name] = $info['expected'];
            self::output(SetupMessages::scriptVerbName, 'added', $name);
        }
        $composer->save();
        self::output(SetupMessages::composerJsonUpdated);
    }

    protected function runInstallCommands(Composer $composer): void
    {
        $pending = $composer->getPendingPackages($this);
        if (empty($pending)) {
            self::output(SetupMessages::noMissingPackagesFound);

            return;
        }
        foreach ($pending as $name => $info) {
            $key = $info['key'];
            $command = ($key === 'require-dev') ? "composer require --dev $name" : "composer require $name";
            self::output(SetupMessages::installingPackageForTask, $name, $info['task']);
            self::output(SetupMessages::runningCommand, $command);
            if (! $this->projectContext->runProcess($command)) {
                self::output(SetupMessages::commandFailed, $command);
            }
        }
    }

    /**
     * writes a default configuration file if it doesn't exist or merges deltas
     *
     * @param  array<string,mixed>  $data
     */
    public function writeConfig(ProjectContext $projectContext, array $data = []): void
    {
        if ($projectContext->isLaravel()) {
            self::output(SetupMessages::laravelDetected);
        }
        if ($projectContext->isWorkbench()) {
            self::output(SetupMessages::workbenchDetected);
        }

        $configPath = $projectContext->fullPath(self::CONFIG_BASE);
        $isUpdate = $projectContext->filesystem->exists($configPath);

        if ($isUpdate && empty($data)) {
            try {
                $data = Neon::decode($projectContext->filesystem->get($configPath)) ?? [];
            } catch (\Exception $e) {
                $data = [];
            }
        }

        if (is_scalar($data)) {
            $data = [];
        }

        if (! $isUpdate) {
            /*
            * build the default configuration
            */
            $data = [
                'test' => 'pest',
                'quick' => ['pint', 'test', 'markdown'],
                'release' => ['pint', 'analyse', 'coverage', 'markdown'],
                'markdown' => 'SchenkeIo\PackagingTools\Workbench\MakeMarkdown::run',
                'pint' => true,
                'customTasks' => [],
            ];
        }

        $deltas = $this->getC2pDeltas();
        if (empty($deltas)) {
            if ($isUpdate) {
                self::output(SetupMessages::configFileUpToDate, self::CONFIG_BASE);

                return;
            }
        } else {
            self::output(SetupMessages::mergingKeysIntoConfig, self::CONFIG_BASE);
            foreach ($deltas as $key => $value) {
                self::output(SetupMessages::listKeyAndValue, $key, json_encode($value));
            }
            $data = array_merge($data, $deltas);
        }

        $processor = new Processor;
        // generate configuration from schema
        $processed = $processor->process($this->getSchema(), $data);
        $neon = $this->getNeonWithComments($processed);
        // write neon content to config file
        if ($projectContext->filesystem->put($configPath, $neon) === false) {
            self::output(SetupMessages::errorWritingConfig, self::CONFIG_BASE);

            return;
        }
        if ($isUpdate) {
            self::output(SetupMessages::configFileUpdated, self::CONFIG_BASE);
        } else {
            self::output(SetupMessages::configFileCreated, self::CONFIG_BASE);
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

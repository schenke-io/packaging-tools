<?php

namespace SchenkeIo\PackagingTools\Setup;

use SchenkeIo\PackagingTools\Contracts\SetupDefinitionInterface;
use SchenkeIo\PackagingTools\Exceptions\PackagingToolException;

/**
 * Handles interactions with composer.json.
 *
 * This class provides methods to read, modify, and save the composer.json file.
 * It manages script entries, package requirements, and identifies available
 * commands for the task.
 *
 * Key functionalities include:
 * - Loading and decoding composer.json in the constructor
 * - Saving modifications back to the filesystem with save()
 * - Checking if specific packages are installed using hasPackage() or packageFound()
 * - Detecting known tool packages from composer.json with getToolsFromComposer()
 * - Setting script commands via setCommands()
 * - Identifying pending script and package changes with getPendingScripts() and getPendingPackages()
 * - Collecting and adding required packages for tasks using setPackages() and setAddPackages()
 * - Managing standard update scripts ('low' and 'stable')
 */
class Composer
{
    /**
     * @var array<string,mixed>
     */
    public array $composer = [];

    /**
     * @var array<int,string>
     */
    protected array $neededPackages = [];

    public Requirements $requirements;

    /**
     * @var array<int,string>
     */
    protected array $runLines = [];

    /**
     * @throws PackagingToolException
     */
    public function __construct(
        protected ProjectContext $projectContext = new ProjectContext
    ) {
        $this->requirements = new Requirements;
        $this->composer = json_decode($this->projectContext->composerJsonContent, true);
        /*
         * add some special commands
         */
        $this->composer['scripts']['low'] = 'composer update --prefer-lowest --prefer-dist';
        $this->composer['scripts']['stable'] = 'composer update --prefer-stable --prefer-dist';
    }

    /**
     * persists the modified composer.json back to disk
     */
    public function save(): void
    {
        $this->projectContext->filesystem->put(
            $this->projectContext->composerJsonPath,
            json_encode(
                $this->composer,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            ) ?: ''
        );
    }

    /**
     * checks if a package is already present in composer.json
     */
    public function hasPackage(string $packageWanted, ?string $key = null): bool
    {
        /**
         * if no key provided, search in both require and require-dev
         */
        $sources = is_null($key) ? ['require', 'require-dev'] : [$key];
        foreach ($sources as $source) {
            if (isset($this->composer[$source][$packageWanted])) {
                return true;
            }
        }

        return false;
    }

    /**
     * checks if a package is already present in composer.json
     */
    public static function packageFound(string $packageWanted, ?string $key = null, ?ProjectContext $projectContext = null): bool
    {
        /**
         * creates a new instance of Composer to check its loaded data
         */
        $me = new self($projectContext ?? new ProjectContext);

        return $me->hasPackage($packageWanted, $key);
    }

    /**
     * scans composer.json for known tool packages and returns their configuration states
     *
     * @return array<string, string|bool>
     */
    public function getToolsFromComposer(): array
    {
        $foundTools = [];

        $tools = [
            'pestphp/pest' => ['test', 'pest'],
            'phpunit/phpunit' => ['test', 'phpunit'],
            'phpstan/phpstan' => ['analyse', true],
            'phpstan/phpstan-phpunit' => ['analyse', true],
            'larastan/larastan' => ['analyse', true],
            'laravel/pint' => ['pint', true],
            'infection/infection' => ['infection', true],
        ];

        foreach ($tools as $package => $config) {
            if ($this->hasPackage($package)) {
                $foundTools[$config[0]] = $config[1];
            }
        }

        return $foundTools;
    }

    /**
     * updates a script entry in composer.json for a given task
     */
    public function setCommands(string $taskName, SetupDefinitionInterface $task, Config $config): void
    {
        $value = $task->commands($config);
        $this->composer['scripts'][$taskName] = $value;
    }

    /**
     * checks for missing packages required by a task and adds them to a list for installation
     */
    public function setPackages(SetupDefinitionInterface $task, Config $config): void
    {
        /**
         * retrieve package requirements from the task
         */
        $packages = $task->packages($config)->data();
        foreach ($packages as $key => $names) {
            foreach ($names as $name) {
                /**
                 * check if package is not already in composer.json
                 */
                if (! isset($this->composer[$key][$name])) {
                    /**
                     * add installation command to the list of needed packages
                     */
                    if (str_ends_with($key, '-dev')) {
                        $this->neededPackages[] = "composer require --dev $name";
                    } else {
                        $this->neededPackages[] = "composer require $name";
                    }
                }
            }
        }
    }

    /**
     * adds the collected missing packages installation commands to the 'add' script in composer.json
     */
    public function setAddPackages(): void
    {
        $this->composer['scripts']['add'] = array_unique($this->neededPackages);
    }

    /**
     * returns an array of scripts that are missing or different in composer.json
     *
     * @return array<string, array{expected: string|array<int, string>, status: string}>
     */
    public function getPendingScripts(Config $config): array
    {
        $pending = [];
        foreach ($config->taskRegistry->getAllTasks() as $name => $task) {
            $expected = $task->commands($config);
            if ($expected === [] || $expected === '') {
                continue;
            }
            $current = $this->composer['scripts'][$name] ?? null;
            if ($current !== $expected) {
                $pending[$name] = [
                    'expected' => $expected,
                    'status' => is_null($current) ? 'missing' : 'different',
                ];
            }
        }

        return $pending;
    }

    /**
     * returns an array of packages that are missing in composer.json
     *
     * @return array<string, array{key: string, task: string}>
     */
    public function getPendingPackages(Config $config): array
    {
        $pending = [];
        foreach ($config->taskRegistry->getAllTasks() as $task) {
            $packages = $task->packages($config)->data();
            foreach ($packages as $key => $names) {
                foreach ($names as $name) {
                    if (! isset($this->composer['require'][$name]) && ! isset($this->composer['require-dev'][$name])) {
                        $pending[$name] = [
                            'key' => $key,
                            'task' => $task->explainTask(),
                        ];
                    }
                }
            }
        }

        return $pending;
    }
}

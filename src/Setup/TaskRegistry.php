<?php

namespace SchenkeIo\PackagingTools\Setup;

use SchenkeIo\PackagingTools\Contracts\SetupDefinitionInterface;
use SchenkeIo\PackagingTools\Setup\Definitions\AnalyseDefinition;
use SchenkeIo\PackagingTools\Setup\Definitions\BadgeDefinition;
use SchenkeIo\PackagingTools\Setup\Definitions\CoverageDefinition;
use SchenkeIo\PackagingTools\Setup\Definitions\InfectionDefinition;
use SchenkeIo\PackagingTools\Setup\Definitions\MarkdownDefinition;
use SchenkeIo\PackagingTools\Setup\Definitions\MigrationsDefinition;
use SchenkeIo\PackagingTools\Setup\Definitions\PintDefinition;
use SchenkeIo\PackagingTools\Setup\Definitions\QuickDefinition;
use SchenkeIo\PackagingTools\Setup\Definitions\ReleaseDefinition;
use SchenkeIo\PackagingTools\Setup\Definitions\SqlCacheDefinition;
use SchenkeIo\PackagingTools\Setup\Definitions\TestDefinition;

/**
 * Class TaskRegistry
 *
 * Registry for all available setup tasks.
 *
 * Main Responsibilities:
 * - Registration: Handles both core and custom setup task registration.
 * - Retrieval: Provides methods to find specific tasks or all registered tasks.
 * - Discovery: Optionally registers all core tasks on instantiation.
 *
 * Usage Example:
 * ```php
 * $registry = new TaskRegistry();
 * $task = $registry->getTask('analyse');
 * ```
 */
class TaskRegistry
{
    /**
     * @var array<string, SetupDefinitionInterface>
     */
    protected array $tasks = [];

    /**
     * Initialize the registry and optionally register core tasks.
     *
     * @param  bool  $registerCoreTasks  Whether to register core tasks automatically.
     */
    public function __construct(bool $registerCoreTasks = true)
    {
        if ($registerCoreTasks) {
            $this->registerTask('analyse', new AnalyseDefinition);
            $this->registerTask('badges', new BadgeDefinition);
            $this->registerTask('coverage', new CoverageDefinition);
            $this->registerTask('infection', new InfectionDefinition);
            $this->registerTask('markdown', new MarkdownDefinition);
            $this->registerTask('migrations', new MigrationsDefinition);
            $this->registerTask('pint', new PintDefinition);
            $this->registerTask('quick', new QuickDefinition);
            $this->registerTask('release', new ReleaseDefinition);
            $this->registerTask('sql-cache', new SqlCacheDefinition);
            $this->registerTask('test', new TestDefinition);
        }
    }

    /**
     * Register a specific task instance with a unique name.
     */
    public function registerTask(string $name, SetupDefinitionInterface $task): void
    {
        $this->tasks[$name] = $task;
    }

    /**
     * Return the full list of currently registered task instances, sorted by name.
     *
     * @return array<string, SetupDefinitionInterface>
     */
    public function getAllTasks(): array
    {
        // Sort tasks by name alphabetically for consistent schema generation
        ksort($this->tasks);

        // return the complete list of registered tasks
        return $this->tasks;
    }

    /**
     * returns a specific task by name, or null if it's not found
     */
    public function getTask(string $name): ?SetupDefinitionInterface
    {
        return $this->tasks[$name] ?? null;
    }
}

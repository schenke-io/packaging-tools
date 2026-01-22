<?php

namespace SchenkeIo\PackagingTools\Setup;

use SchenkeIo\PackagingTools\Contracts\SetupDefinitionInterface;
use SchenkeIo\PackagingTools\Setup\Definitions\BaseDefinition;

/**
 * Registry for all available setup tasks.
 *
 * This class handles the discovery and registration of core task definitions
 * and allows for dynamic registration of custom tasks. It scans the 'Definitions'
 * directory to automatically find and instantiate tasks that implement
 * the SetupDefinitionInterface.
 *
 * Methods:
 * - __construct(): Optionally scans the core Definitions directory to auto-register tasks.
 * - registerTask(): Manually registers a task instance with a specific name.
 * - getAllTasks(): Returns the full list of currently registered task instances.
 * - getTask(): Retrieves a specific task instance by its registration name.
 */
class TaskRegistry
{
    /**
     * @var array<string, SetupDefinitionInterface>
     */
    protected array $tasks = [];

    public function __construct(bool $scan = true)
    {
        if (! $scan) {
            return;
        }
        // Register core tasks from the Definitions directory
        $definitionsDir = __DIR__.'/Definitions';
        foreach (glob($definitionsDir.'/*Definition.php') ?: [] as $file) {
            $className = 'SchenkeIo\\PackagingTools\\Setup\\Definitions\\'.basename($file, '.php');
            if (class_exists($className)) {
                $reflection = new \ReflectionClass($className);
                if ($reflection->isInstantiable() && $reflection->implementsInterface(SetupDefinitionInterface::class)) {
                    try {
                        $baseName = str_replace('Definition', '', basename($file, '.php'));
                        $taskName = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $baseName));
                        /** @var SetupDefinitionInterface $task */
                        $task = new $className;
                        if ($task instanceof BaseDefinition) {
                            $task->setTaskName($taskName);
                        }
                        $this->registerTask($taskName, $task);
                    } catch (\ArgumentCountError $e) {
                        // Skip classes that require arguments in constructor
                        continue;
                    }
                }
            }
        }
    }

    public function registerTask(string $name, SetupDefinitionInterface $task): void
    {
        $this->tasks[$name] = $task;
    }

    /**
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

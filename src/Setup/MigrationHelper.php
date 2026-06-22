<?php

namespace SchenkeIo\PackagingTools\Setup;

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\PackagingTools\Exceptions\PackagingToolException;

/**
 * Class MigrationHelper
 *
 * Helper class for migration-related tasks.
 *
 * Main Responsibilities:
 * - Target Resolution: Determines which tables and connections should be used for migration generation.
 * - Table Discovery: Scans model directories to identify Eloquent model tables.
 * - Migration Cleanup: Provides methods to clear existing migration files.
 * - Class Discovery: Helper to resolve fully qualified class names from PHP files.
 *
 * Usage Example:
 * ```php
 * $targets = MigrationHelper::resolveMigrationTargets($config, $projectContext);
 * $tables = MigrationHelper::getTablesFromModels($projectContext);
 * ```
 */
class MigrationHelper
{
    /**
     * List of system tables that are always included in migration generation.
     *
     * @var array<int, string>
     */
    public static array $systemTables = [
        'migrations',
        'jobs',
        'batches',
        'cache',
        'cache_locks',
        'failed_jobs',
        'job_batches',
        'password_reset_tokens',
        'sessions',
    ];

    /**
     * Resolves the connection and tables for migration generation.
     *
     * @param  Config  $config  The configuration object.
     * @param  ProjectContext  $projectContext  The project context.
     * @return array{connection: string, tables: list<string>}
     */
    public static function resolveMigrationTargets(Config $config, ProjectContext $projectContext): array
    {
        $migrationsConfig = $config->config->migrations ?? null;
        $connection = '';
        $tables = self::$systemTables;

        if (is_string($migrationsConfig) && $migrationsConfig !== '') {
            if (str_contains($migrationsConfig, ':')) {
                [$connection, $tableList] = explode(':', $migrationsConfig, 2);
                if ($tableList === '*') {
                    $tables = array_merge($tables, self::getTablesFromModels($projectContext));
                } elseif ($tableList !== '') {
                    $tables = array_merge($tables, explode(',', $tableList));
                }
            } else {
                $connection = $migrationsConfig;
                $tables = array_merge($tables, self::getTablesFromModels($projectContext));
            }
        } else {
            $tables = array_merge($tables, self::getTablesFromModels($projectContext));
        }

        $tables = array_unique(array_filter(array_map('trim', $tables)));
        sort($tables);

        return [
            'connection' => $connection,
            'tables' => $tables,
        ];
    }

    /**
     * Clears existing migration files from the migrations directory.
     *
     * @param  mixed  $event  Optional event context.
     * @param  ProjectContext|null  $projectContext  Optional override for project context.
     */
    public static function clearMigrations(mixed $event = null, ?ProjectContext $projectContext = null): void
    {
        $projectContext = $projectContext ?? new ProjectContext;
        $path = $projectContext->getMigrationPath();
        $fullPath = $projectContext->fullPath($path);
        if ($projectContext->filesystem->isDirectory($fullPath)) {
            $projectContext->filesystem->cleanDirectory($fullPath);
        }
    }

    /**
     * Scans the model directory and retrieves table names from found Eloquent models.
     *
     * @param  ProjectContext  $projectContext  The project context.
     * @return list<string> The list of table names.
     */
    public static function getTablesFromModels(ProjectContext $projectContext): array
    {
        $tables = [];
        try {
            $modelPath = $projectContext->getModelPath();
        } catch (PackagingToolException) {
            return [];
        }
        foreach ($projectContext->filesystem->allFiles($modelPath) as $file) {
            if ($file->getExtension() === 'php') {
                $className = self::getClassNameFromFile($file->getRealPath(), $projectContext);
                if ($className && class_exists($className)) {
                    try {
                        $reflection = new \ReflectionClass($className);
                        if ($reflection->isInstantiable() && $reflection->isSubclassOf(Model::class)) {
                            /** @var Model $model */
                            $model = new $className;
                            $tables[] = $model->getTable();
                        }
                    } catch (\Throwable) {
                        // ignore classes that cannot be reflected or instantiated
                    }
                }
            }
        }

        return $tables;
    }

    /**
     * Attempts to resolve the class name from a PHP file by parsing its namespace and class name.
     *
     * @param  string  $path  The absolute path to the PHP file.
     * @param  ProjectContext  $projectContext  The project context.
     * @return string|null The fully qualified class name or null if not found.
     */
    public static function getClassNameFromFile(string $path, ProjectContext $projectContext): ?string
    {
        $content = $projectContext->filesystem->get($path);
        $tokens = token_get_all($content);
        $namespace = '';
        $class = '';

        for ($i = 0; $i < count($tokens); $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_NAMESPACE) {
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_NAME_QUALIFIED, T_STRING, T_NS_SEPARATOR])) {
                        $namespace .= $tokens[$j][1];
                    } elseif ($tokens[$j] === ';' || $tokens[$j] === '{') {
                        break;
                    }
                }
            }
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_CLASS) {
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $class = $tokens[$j][1];
                        break 2;
                    }
                }
            }
        }

        return $class ? ($namespace ? $namespace.'\\'.$class : $class) : null;
    }
}

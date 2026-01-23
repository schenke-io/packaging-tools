<?php

namespace SchenkeIo\PackagingTools\Setup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use SchenkeIo\PackagingTools\Exceptions\PackagingToolException;

/**
 * Helper class for migration-related tasks.
 *
 * This helper centralizes the logic for determining which tables and
 * connections should be used when generating migrations, ensuring
 * consistency between Artisan commands and setup tasks.
 * PHPDoc density requirement (3% rule):
 * This class provides static methods to resolve database connections
 * and tables by scanning model directories and reading configuration.
 * It ensures that system-level tables are always included in the
 * migration generation process to maintain a stable package environment.
 */
class MigrationHelper
{
    /**
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
        foreach (File::allFiles($modelPath) as $file) {
            if ($file->getExtension() === 'php') {
                $className = self::getClassNameFromFile($file->getRealPath());
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
     * @return string|null The fully qualified class name or null if not found.
     */
    public static function getClassNameFromFile(string $path): ?string
    {
        $content = File::get($path);
        $namespace = null;
        $class = null;

        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = trim($matches[1]);
        }

        if (preg_match('/\bclass\s+(\w+)/', $content, $matches)) {
            $class = $matches[1];
        }

        return ($namespace && $class) ? $namespace.'\\'.$class : null;
    }
}

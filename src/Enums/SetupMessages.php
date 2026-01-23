<?php

namespace SchenkeIo\PackagingTools\Enums;

/**
 * All messages used during the setup process
 *
 * This enumeration centralizes all status, information, and error messages
 * that are displayed to the user during the package setup and update commands.
 * Each case represents a specific message template, often including
 * placeholders for dynamic values that can be inserted using the
 * `format()` method.
 *
 * The `format()` method provides a convenient way to inject dynamic values
 * into the messages using `sprintf` syntax. By centralizing these messages,
 * the package maintains a consistent user interface and simplifies
 * internationalization or future message updates.
 *
 * These messages cover various stages of the package lifecycle:
 * - Configuration creation and updates
 * - Composer JSON modifications
 * - Dependency management and installation
 * - Directory and file creation
 * - Result reporting and error handling
 */
enum SetupMessages: string
{
    /**
     * @param  string  $configBase
     */
    case runSetupConfigToCreate = "run 'composer setup config' to create a new configuration in '%s':";

    /**
     * @param  string  $key
     * @param  string  $jsonValue
     */
    case listKeyAndValue = ' - add %s: %s';

    case everythingUpToDate = 'Everything is up to date.';

    /**
     * @param  string  $configBase
     */
    case runSetupConfigToDoChanges = "run 'composer setup config' to add these keys to '%s':";

    /**
     * @param  string  $composerJson
     */
    case runSetupUpdateToDoChanges = "run 'composer setup update' to add these elements to 'composer.json':";

    /**
     * @param  string  $verb
     * @param  string  $name
     */
    case listVerbScript = ' - %s script %s';

    /**
     * @param  string  $name
     * @param  string  $task
     */
    case listAddPackageForTask = ' - add package %s (for task: %s)';

    /**
     * @param  string  $parameter
     */
    case technoclExplicit = "unknown parameter '%s'";

    case noScriptChangesPending = 'No script changes pending.';

    /**
     * @param  string  $verb
     * @param  string  $name
     */
    case scriptVerbName = '%s script: %s';

    case composerJsonUpdated = 'composer.json updated.';

    case noMissingPackagesFound = 'No missing packages found.';

    /**
     * @param  string  $name
     * @param  string  $task
     */
    case installingPackageForTask = 'install package %s for task: %s';

    /**
     * @param  string  $command
     */
    case runningCommand = 'running: %s';

    case laravelDetected = 'Laravel detected and considered.';

    case orchestraWorkbenchDetected = 'Orchestra Workbench detected and considered.';

    /**
     * @param  string  $configBase
     */
    case configFileUpToDate = 'config file %s is already up to date.';

    /**
     * @param  string  $configBase
     */
    case mergingKeysIntoConfig = 'merge these keys from composer.json into %s:';

    /**
     * @param  string  $configBase
     */
    case configFileUpdated = 'config file %s updated.';

    /**
     * @param  string  $configBase
     */
    case configFileCreated = 'config file %s created.';

    case readmeGenerated = 'README.md generated successfully!';

    /**
     * @param  string  $markdownDir
     */
    case createdDirectory = 'Created directory: %s';

    /**
     * @param  string  $filePath
     */
    case createdFile = 'Created file: %s';

    /**
     * @param  string  $filePath
     */
    case infoAssemblingMarkdown = ' INFO  Assembling Markdown to %s';

    /**
     * @param  string  $badgeInfo
     */
    case infoBadgeGenerated = ' INFO  Badge generated: %s';

    /**
     * @param  string  $filename
     */
    case cleanedConnectionCalls = 'Cleaned connection calls from: %s';

    case composerJsonUpdatedWithScripts = 'composer.json updated with scripts';

    /**
     * @param  string  $path
     */
    case sqlCacheDumped = 'SQL cache dumped to %s';

    /**
     * @param  string  $filename
     */
    case errorWritingConfig = 'error writing %s';

    /**
     * @param  string  $filename
     */
    case errorReadingConfig = 'error reading %s';

    /**
     * @param  string  $filename
     */
    case errorParsingConfig = 'error parsing %s';

    /**
     * @param  string  $command
     */
    case commandFailed = 'command failed: %s';

    public function format(mixed ...$args): string
    {
        return sprintf($this->value, ...$args);
    }
}

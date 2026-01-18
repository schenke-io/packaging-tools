<?php

namespace SchenkeIo\PackagingTools\Exceptions;

use Exception;

/**
 * Base exception class for all packaging tool related errors.
 *
 * This class provides static factory methods to create descriptive exceptions
 * for various error conditions encountered by the packaging tools. It acts
 * as a centralized registry for all expected failure modes within the package,
 * ensuring consistent error messaging across different components.
 *
 * Static Factory Methods:
 * - configError(): For syntax or logical errors in the .neon configuration.
 * - invalidConfigFile(): When the configuration file cannot be parsed.
 * - composerJsonNotFound() / invalidComposerJson(): Issues with the project's composer file.
 * - fileNotFound() / projectRootNotFound(): File system access or location errors.
 * - unsupportedFileExtension(): When processing files with unexpected formats.
 * - workflowNotFound(): Missing GitHub action definitions for badges.
 * - pathDetectionFailed(): Inability to locate source data for specific badge types.
 */
class PackagingToolException extends Exception
{
    /**
     * Create an exception for general configuration errors.
     */
    public static function configError(string $filename, string $message): self
    {
        return new self("Configuration error in '$filename': $message");
    }

    /**
     * Create an exception for invalid configuration file format.
     */
    public static function invalidConfigFile(string $filename, string $message): self
    {
        return new self("Invalid configuration file '$filename': $message");
    }

    /**
     * Create an exception when composer.json is not found.
     */
    public static function composerJsonNotFound(string $path): self
    {
        return new self("composer.json not found: $path");
    }

    /**
     * Create an exception for invalid composer.json content.
     */
    public static function invalidComposerJson(string $message): self
    {
        return new self("Invalid composer.json: $message");
    }

    /**
     * Create an exception when a required file is missing.
     */
    public static function fileNotFound(string $path): self
    {
        return new self("File does not exist at path $path.");
    }

    /**
     * Create an exception for unsupported file extensions.
     */
    public static function unsupportedFileExtension(string $extension): self
    {
        return new self("File extension '$extension' not supported");
    }

    /**
     * Create an exception when the project root directory is invalid.
     */
    public static function projectRootNotFound(string $path): self
    {
        return new self("Project root is not a directory: $path");
    }

    /**
     * Create an exception when the project root is not set.
     */
    public static function projectRootNotSet(): self
    {
        return new self('Project root is not set');
    }

    /**
     * Create an exception for unsupported private repositories.
     */
    public static function privateRepositoryNotSupported(string $projectName): self
    {
        return new self("Private repository '$projectName' is not supported for public badges");
    }

    /**
     * Create an exception when a GitHub workflow file is missing.
     */
    public static function workflowNotFound(string $workflowPath): self
    {
        return new self("Workflow file not found at: $workflowPath");
    }

    /**
     * Create an exception when automatic path detection fails for a badge type.
     */
    public static function pathDetectionFailed(string $badgeType): self
    {
        return new self("Could not detect path for badge type: $badgeType");
    }
}

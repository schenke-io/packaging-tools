<?php

namespace SchenkeIo\PackagingTools\Markdown;

use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Builder for the Markdown generation script.
 *
 * This class detects the project structure (Laravel app or package) and
 * generates an appropriate script to assemble the project's documentation.
 * It intelligently sorts Markdown components and configures the
 * MarkdownAssembler with defaults like headers, badges, and class documentation.
 *
 * Capabilities:
 * - Environment Detection: Differentiates between standard Laravel apps and package structures.
 * - Script Generation: Can output a Laravel console command or a standalone PHP script.
 * - Auto-ordering: Ensures standard documentation files (e.g., header, installation) are correctly sequenced.
 * - Facade-like Setup: Pre-populates the generated script with standard assembly calls.
 *
 * Key Methods:
 * - build(): Orchestrates the script generation based on the current ProjectContext.
 * - buildConsoleCommand(): Creates a specialized Laravel command for documentation.
 * - buildPlainScript(): Creates a vendor-autoloaded standalone script.
 */
class MakerScriptBuilder
{
    public const PLAIN_SCRIPT = '.make-markdown.php';

    public function __construct(protected ProjectContext $projectContext) {}

    public function build(): string
    {
        $markdownDir = $this->projectContext->fullPath('workbench/resources/md');
        if (! $this->projectContext->filesystem->isDirectory($markdownDir)) {
            $markdownDir = $this->projectContext->fullPath('resources/md');
        }

        $relativeMarkdownDir = str_replace($this->projectContext->projectRoot.'/', '', $markdownDir);

        $components = [];
        if ($this->projectContext->filesystem->isDirectory($markdownDir)) {
            $files = $this->projectContext->filesystem->files($markdownDir);
            foreach ($files as $file) {
                if ($file->getExtension() === 'md') {
                    $components[] = $file->getFilename();
                }
            }
        }
        // ensure default order if they exist
        $order = ['header.md', 'installation.md', 'usage.md', 'features.md', 'contributing.md'];
        $sortedComponents = [];
        foreach ($order as $file) {
            if (in_array($file, $components)) {
                $sortedComponents[] = $file;
                $components = array_diff($components, [$file]);
            }
        }
        $sortedComponents = array_merge($sortedComponents, $components);

        if ($this->projectContext->sourceRoot === 'app') {
            return $this->buildConsoleCommand('app/Console/Commands/MakeMarkdown.php', $relativeMarkdownDir, $sortedComponents);
        }

        if ($this->projectContext->filesystem->isDirectory($this->projectContext->fullPath('workbench/app'))) {
            return $this->buildConsoleCommand('workbench/app/Console/Commands/MakeMarkdown.php', $relativeMarkdownDir, $sortedComponents);
        }

        return $this->buildPlainScript(self::PLAIN_SCRIPT, $relativeMarkdownDir, $sortedComponents);
    }

    /**
     * @param  array<int, string>  $components
     */
    protected function buildConsoleCommand(string $path, string $markdownDir, array $components): string
    {
        $namespace = str_starts_with($path, 'workbench') ? 'Workbench\App\Console\Commands' : 'App\Console\Commands';
        $componentCalls = '';
        foreach ($components as $component) {
            if ($component === 'header.md') {
                $componentCalls .= "            ->addMarkdown('header.md')\n            ->badges()->all()\n";
            } elseif ($component === 'usage.md') {
                $componentCalls .= "            ->addMarkdown('usage.md')\n            ->classes()->all()\n";
            } else {
                $componentCalls .= "            ->addMarkdown('$component')\n";
            }
        }

        $content = <<<PHP
<?php

namespace $namespace;

use Illuminate\Console\Command;
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;

class MakeMarkdown extends Command
{
    protected \$signature = 'make:markdown';
    protected \$description = 'Generate project markdown files';

    public function handle(): void
    {
        \$assembler = new MarkdownAssembler('$markdownDir');

        \$assembler->addTableOfContents()
$componentCalls            ->writeMarkdown('README.md');

        \$this->info('README.md generated successfully!');
    }
}
PHP;
        $fullPath = $this->projectContext->fullPath($path);
        $this->ensureDirectory(dirname($fullPath));
        $this->projectContext->filesystem->put($fullPath, $content);

        return $path;
    }

    /**
     * @param  array<int, string>  $components
     */
    protected function buildPlainScript(string $path, string $markdownDir, array $components): string
    {
        $componentCalls = '';
        foreach ($components as $component) {
            if ($component === 'header.md') {
                $componentCalls .= "    ->addMarkdown('header.md')\n    ->badges()->all()\n";
            } elseif ($component === 'usage.md') {
                $componentCalls .= "    ->addMarkdown('usage.md')\n    ->classes()->all()\n";
            } else {
                $componentCalls .= "    ->addMarkdown('$component')\n";
            }
        }

        $content = <<<PHP
<?php

require_once __DIR__ . '/vendor/autoload.php';

use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;
use SchenkeIo\PackagingTools\Setup\Config;

\$assembler = new MarkdownAssembler('$markdownDir');

\$assembler->addTableOfContents()
$componentCalls    ->writeMarkdown('README.md');

Config::output("README.md generated successfully!");
PHP;
        $this->projectContext->filesystem->put($this->projectContext->fullPath($path), $content);

        return $path;
    }

    protected function ensureDirectory(string $dir): void
    {
        if (! $this->projectContext->filesystem->isDirectory($dir)) {
            $this->projectContext->filesystem->makeDirectory($dir, 0755, true);
        }
    }
}

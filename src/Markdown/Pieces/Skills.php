<?php

namespace SchenkeIo\PackagingTools\Markdown\Pieces;

use SchenkeIo\PackagingTools\Contracts\MarkdownPieceInterface;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Piece for including skill-based documentation.
 *
 * This class scans a specific directory (resources/boost/skills/) for
 * skill documentation files (SKILL.md). It allows for selective or
 * collective inclusion of these skills into the final documentation.
 */
class Skills implements MarkdownPieceInterface
{
    /**
     * @var array<int, string>
     */
    protected array $skills = [];

    protected bool $addAll = false;

    protected bool $isOverview = false;

    /**
     * Register a specific skill.
     */
    public function add(string $skillName): self
    {
        if (! in_array($skillName, $this->skills)) {
            $this->skills[] = $skillName;
        }

        return $this;
    }

    /**
     * Register all found skills.
     */
    public function all(): self
    {
        $this->addAll = true;

        return $this;
    }

    /**
     * Set the piece to display an overview table of skills.
     */
    public function asOverview(): self
    {
        $this->isOverview = true;

        return $this;
    }

    /**
     * Get the combined Markdown content of all registered skills.
     */
    public function getContent(ProjectContext $projectContext, string $markdownSourceDir): string
    {
        if ($this->addAll) {
            $this->addAllSkills($projectContext);
        }

        if ($this->isOverview) {
            return $this->getOverviewContent($projectContext);
        }

        $contents = [];
        foreach ($this->skills as $skillName) {
            $filePath = $projectContext->fullPath("resources/boost/skills/$skillName/SKILL.md");
            if ($projectContext->filesystem->exists($filePath)) {
                $skillContent = $projectContext->filesystem->get($filePath);
                $contents[] = $this->processContent($skillContent, $projectContext, $skillName);
            }
        }

        return trim(implode("\n\n", $contents));
    }

    protected function getOverviewContent(ProjectContext $projectContext): string
    {
        $data = [['Title', 'Description']];
        foreach ($this->skills as $skillName) {
            $relativeSkillPath = "resources/boost/skills/$skillName/SKILL.md";
            $filePath = $projectContext->fullPath($relativeSkillPath);
            if ($projectContext->filesystem->exists($filePath)) {
                $skillContent = $projectContext->filesystem->get($filePath);
                $metadata = $this->getMetadata($skillContent);
                if ($metadata) {
                    $title = sprintf('[%s](%s)', $metadata['title'], $relativeSkillPath);
                    $data[] = [$title, $metadata['description']];
                }
            }
        }

        return (new Tables)->getTableFromArray($data);
    }

    protected function addAllSkills(ProjectContext $projectContext): void
    {
        $skillsDir = $projectContext->fullPath('resources/boost/skills');
        if (! $projectContext->filesystem->isDirectory($skillsDir)) {
            return;
        }

        $directories = $projectContext->filesystem->directories($skillsDir);
        foreach ($directories as $dir) {
            $skillName = basename($dir);
            if ($projectContext->filesystem->exists("$dir/SKILL.md")) {
                $this->add($skillName);
            }
        }
    }

    /**
     * Write AI guidelines in Blade format to a file.
     */
    public function writeGuidelines(ProjectContext $projectContext, string $path): void
    {
        if ($this->addAll) {
            $this->addAllSkills($projectContext);
        }

        $composerJson = json_decode($projectContext->composerJsonContent, true);
        $packageName = $composerJson['name'] ?? $projectContext->projectName;
        $packageDescription = $composerJson['description'] ?? '';

        $content = "## $packageName\n\n";
        $content .= "$packageDescription\n\n";
        $content .= "### Features\n\n";

        foreach ($this->skills as $skillName) {
            $filePath = $projectContext->fullPath("resources/boost/skills/$skillName/SKILL.md");
            if ($projectContext->filesystem->exists($filePath)) {
                $skillContent = $projectContext->filesystem->get($filePath);
                $metadata = $this->getMetadata($skillContent);
                if ($metadata) {
                    $content .= "- {$metadata['title']}: {$metadata['description']}\n";
                }
            }
        }

        $projectContext->filesystem->put($projectContext->fullPath($path), $content);
    }

    /**
     * @return array<string, string>|null
     */
    protected function getMetadata(string $content): ?array
    {
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n/s', $content, $matches)) {
            $yaml = $matches[1];
            $metadata = [];

            if (preg_match('/^type:\s*(.*)$/m', $yaml, $typeMatches)) {
                $metadata['type'] = trim($typeMatches[1]);
            }
            if (preg_match('/^title:\s*(.*)$/m', $yaml, $titleMatches)) {
                $metadata['title'] = trim($titleMatches[1]);
            } elseif (preg_match('/^name:\s*(.*)$/m', $yaml, $nameMatches)) {
                $metadata['title'] = trim($nameMatches[1]);
            }

            if (preg_match('/^description:\s*(.*)$/m', $yaml, $descMatches)) {
                $metadata['description'] = trim($descMatches[1]);
            }
            if (preg_match('/^timestamp:\s*(.*)$/m', $yaml, $tsMatches)) {
                $metadata['timestamp'] = trim($tsMatches[1]);
            }

            return $metadata;
        }

        return null;
    }

    /**
     * Process skill content: strip YAML frontmatter and transform tags.
     */
    protected function processContent(string $content, ProjectContext $projectContext, string $skillName): string
    {
        // Strip YAML frontmatter
        $content = preg_replace('/^---\s*\n.*?\n---\s*\n(.*)$/s', '$1', $content) ?? $content;

        // Transform @verbatim tags
        $content = preg_replace_callback(
            '/@verbatim\s*<code-snippet\s+name=".*?"\s+lang="(.*?)">\s*(.*?)\s*<\/code-snippet>\s*@endverbatim/s',
            function ($matches) {
                $lang = $matches[1];
                $code = $matches[2];

                return "```$lang\n$code\n```";
            },
            $content
        ) ?? $content;

        // include sub-skills if they exist
        if (! str_ends_with($skillName, '-none')) {
            $subSkillsDir = $projectContext->fullPath("resources/boost/skills/$skillName/sub-skills");
            if ($projectContext->filesystem->isDirectory($subSkillsDir)) {
                $files = $projectContext->filesystem->files($subSkillsDir);
                foreach ($files as $file) {
                    $subSkillContent = $projectContext->filesystem->get($file);
                    $processedSubSkill = $this->processContent($subSkillContent, $projectContext, $skillName.'/sub-skills-none'); // prevent infinite loop
                    $content .= "\n\n".$processedSubSkill;
                }
            }
        }

        return trim($content);
    }
}

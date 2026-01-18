<?php

namespace SchenkeIo\PackagingTools\Badges\Drivers;

use SchenkeIo\PackagingTools\Contracts\BadgeDriverInterface;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Badge driver for PHPUnit clover coverage reports.
 *
 * This driver parses clover.xml files to calculate the code coverage percentage.
 * It provides the status string and color based on the coverage level.
 * It also includes logic to automatically detect the coverage report path
 * from phpunit.xml configuration files.
 */
class CloverCoverageDriver implements BadgeDriverInterface
{
    /**
     * Get the subject for the coverage badge.
     */
    public function getSubject(): string
    {
        return 'Coverage';
    }

    /**
     * Get the coverage percentage string.
     *
     * @param  ProjectContext  $projectContext  The project context for file operations
     * @param  string  $path  The path to the clover.xml file
     */
    public function getStatus(ProjectContext $projectContext, string $path): string
    {
        return $this->getCoverage($projectContext, $path).'%';
    }

    /**
     * Get the badge color based on coverage percentage.
     *
     * Returns green for >90%, red for <70%, and yellow otherwise.
     *
     * @param  ProjectContext  $projectContext  The project context for file operations
     * @param  string  $path  The path to the clover.xml file
     */
    public function getColor(ProjectContext $projectContext, string $path): string
    {
        $coverage = $this->getCoverage($projectContext, $path);
        if ($coverage > 90) {
            return '27AE60'; // Green
        } elseif ($coverage < 70) {
            return 'C0392B'; // Red
        } else {
            return 'F1C40F'; // Yellow
        }
    }

    /**
     * Get the URL for a remote badge.
     *
     * @param  ProjectContext  $projectContext  The project context for file operations
     * @param  string  $path  The path to the source data
     */
    public function getUrl(ProjectContext $projectContext, string $path): ?string
    {
        return null;
    }

    /**
     * Detect the path to the clover coverage report in the project.
     *
     * Scans phpunit.xml and phpunit.xml.dist for clover log target or outputFile.
     *
     * @param  ProjectContext  $projectContext  The project context for file operations
     */
    public function detectPath(ProjectContext $projectContext): ?string
    {
        foreach (['phpunit.xml', 'phpunit.xml.dist'] as $file) {
            $phpunitXml = $projectContext->fullPath($file);
            if ($projectContext->filesystem->exists($phpunitXml)) {
                $content = $projectContext->filesystem->get($phpunitXml);
                if (preg_match('/<(?:log|report)[\s\S]+?type="coverage-clover"[\s\S]+?target="([^"]+)"/', $content, $matches)) {
                    return $matches[1];
                }
                if (preg_match('/<clover[\s\S]+?outputFile="([^"]+)"/', $content, $matches)) {
                    return $matches[1];
                }
            }
        }

        return null;
    }

    /**
     * Calculates the coverage percentage from a clover.xml file.
     *
     * @param  ProjectContext  $projectContext  The project context for file operations
     * @param  string  $filepath  The path to the clover.xml file
     */
    private function getCoverage(ProjectContext $projectContext, string $filepath): int
    {
        $fullPath = $projectContext->fullPath($filepath);
        $content = $projectContext->filesystem->get($fullPath);
        if ($content === '') {
            return 0;
        }

        $dom = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $result = $dom->loadXML($content);
        libxml_use_internal_errors($previous);
        if (! $result) {
            return 0;
        }
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//project/metrics');

        if ($nodes instanceof \DOMNodeList && $nodes->length > 0) {
            /** @var \DOMElement $node */
            $node = $nodes->item(0);
            $elements = (int) $node->getAttribute('statements');
            $coveredElements = (int) $node->getAttribute('coveredstatements');

            return (int) round($elements > 0 ? 100 * $coveredElements / $elements : 0, 0);
        }

        return 0;
    }
}

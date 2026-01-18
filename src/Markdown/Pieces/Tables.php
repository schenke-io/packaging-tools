<?php

namespace SchenkeIo\PackagingTools\Markdown\Pieces;

use SchenkeIo\PackagingTools\Contracts\MarkdownPieceInterface;
use SchenkeIo\PackagingTools\Exceptions\PackagingToolException;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

/**
 * Markdown component for generating tables.
 *
 * This class provides methods to build Markdown tables from various data
 * sources, including CSV/TSV/PSV files, CSV strings, and PHP arrays. It
 * handles column alignment by calculating maximum widths and generates
 * the appropriate Markdown table syntax.
 *
 * Capabilities:
 * - Data Source Versatility: Import data from local files, formatted strings, or raw arrays.
 * - Automatic Alignment: Calculates column widths to ensure visually aligned Markdown tables.
 * - Format Support: Recognizes CSV, TSV (tab-separated), and PSV (pipe-separated) file extensions.
 * - Header Separation: Automatically inserts the required Markdown table header separator.
 *
 * Methods:
 * - fromFile(): Add a file path to the buffer for deferred reading.
 * - fromCsvString(): Parse raw CSV data with a custom delimiter.
 * - fromArray(): Merge a PHP array directly into the table data.
 * - getTableFromArray(): The core rendering logic to transform data arrays into Markdown strings.
 */
class Tables implements MarkdownPieceInterface
{
    protected const FILE_DELIMITER = [
        'csv' => ',',
        'tsv' => "\t",
        'psv' => '|',
    ];

    /**
     * buffer for table data
     *
     * @var array<int,mixed>
     */
    protected array $data = [];

    /**
     * files to be read
     *
     * @var array<int,string>
     */
    protected array $files = [];

    public function fromFile(string $filepath): self
    {
        $this->files[] = $filepath;

        return $this;
    }

    public function fromCsvString(string $csv, string $delimiter): self
    {
        foreach (explode("\n", $csv) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $this->data[] = str_getcsv($line, $delimiter);
        }

        return $this;
    }

    /**
     * @param  array<int,mixed>  $data
     */
    public function fromArray(array $data): self
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    public function getContent(ProjectContext $projectContext, string $markdownSourceDir): string
    {
        foreach ($this->files as $filepath) {
            $extension = pathinfo($filepath, PATHINFO_EXTENSION);
            if (! isset(self::FILE_DELIMITER[$extension])) {
                throw PackagingToolException::unsupportedFileExtension($extension);
            }
            $content = $projectContext->filesystem->get($filepath);
            $this->fromCsvString($content, self::FILE_DELIMITER[$extension]);
        }
        $this->files = []; // clear files after reading

        return $this->getTableFromArray($this->data);
    }

    /**
     * @param  array<int,mixed>  $data
     */
    public function getTableFromArray(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        /*
         * calculate the max width per column for proper alignment
         */
        $widths = [];
        foreach ($data as $row) {
            foreach ($row as $col => $value) {
                // store the length of the longest string in each column
                $widths[$col] = max($widths[$col] ?? 0, strlen($value));
            }
        }

        /*
         * build the printf format mask based on column widths
         */
        $mask = '| '.implode(' | ', array_map(fn ($x) => "%-{$x}s", $widths))." |\n";
        $md = '';
        foreach ($data as $rowIndex => $row) {
            // pad row with empty strings if it has fewer columns than the header
            $row = array_pad($row, count($widths), '');
            // format row as markdown table row
            $md .= sprintf($mask, ...$row);
            if ($rowIndex == 0) {
                /*
                 * insert the markdown table separator after the header row
                 */
                $md .= '|';
                foreach ($widths as $col => $width) {
                    // each column separator consists of dashes
                    $md .= str_repeat('-', $width + 2);
                    $md .= '|';
                }
                $md .= "\n";
            }
        }

        return $md;
    }
}

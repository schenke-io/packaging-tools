<?php

namespace SchenkeIo\PackagingTools\Markdown;

use Exception;
use Illuminate\Filesystem\Filesystem;

class Table
{
    protected const FILE_DELIMITER = [
        'csv' => ',',
        'tsv' => "\t",
        'psv' => '|',
    ];

    /**
     * @throws Exception
     */
    public function getTableFromFile(string $filename, Filesystem $filesystem = new Filesystem): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if (! isset(self::FILE_DELIMITER[$extension])) {
            throw new Exception("File extension '{$extension}' not supported");
        }
        $content = $filesystem->get($filename);

        return $this->addTableFromCsvString($content, self::FILE_DELIMITER[$extension]);
    }

    public function addTableFromCsvString(string $csv, string $delimiter): string
    {
        $data = [];
        foreach (explode("\n", $csv) as $line) {
            $data[] = str_getcsv($line, $delimiter);
        }

        return $this->getTableFromArray($data);
    }

    /**
     * @param  array<int,mixed>  $data
     */
    public function getTableFromArray(array $data): string
    {
        /*
         * calculate the max width per column
         */
        $widths = [];
        foreach ($data as $row) {
            foreach ($row as $col => $value) {
                $widths[$col] = max($widths[$col] ?? 0, strlen($value));
            }
        }

        // build the format mask
        $mask = '| '.implode(' | ', array_map(fn ($x) => "%-{$x}s", $widths))." |\n";
        $md = '';
        foreach ($data as $rowIndex => $row) {
            $row = array_pad($row, count($widths), '');
            $md .= sprintf($mask, ...$row);
            if ($rowIndex == 0) {
                $md .= '|';
                foreach ($widths as $col => $width) {
                    $md .= str_repeat('-', $width + 2);
                    $md .= '|';
                }
                $md .= "\n";
            }
        }

        return $md;
    }
}

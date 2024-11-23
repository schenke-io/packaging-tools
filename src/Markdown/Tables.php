<?php

namespace SchenkeIo\PackagingTools\Markdown;

use Exception;

trait Tables
{


    /**
     * @throws Exception
     */
    public function addTableFromFile(string $filename): void
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if (! isset(self::FILE_DELIMITER[$extension])) {
            throw new Exception("File extension '{$extension}' not supported");
        }
        $content = $this->filesystem->get($filename);
        $this->addTableFromCsvString($content, self::FILE_DELIMITER[$extension]);
    }

    public function addTableFromCsvString(string $csv, string $delimiter): void
    {
        $data = [];
        foreach (explode("\n", $csv) as $line) {
            $data[] = str_getcsv($line, $delimiter);
        }
        $this->addTableFromArray($data);
    }

    /**
     * @param  array<int,mixed>  $data
     */
    public function addTableFromArray(array $data): void
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
        $colDivider = ' | ';
        $totalWidth =
            array_sum($widths) +  // widest content of all
            (count($widths) - 1) * (strlen($colDivider)) +  // inner column space
            2 * 2; // space for start/end

        // build the format mask
        $mask = '| '.implode($colDivider, array_map(fn ($x) => "%-{$x}s", $widths))." |\n";
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
        $this->blocks[] = $md;
    }
}

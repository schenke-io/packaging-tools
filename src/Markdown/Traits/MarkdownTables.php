<?php

namespace SchenkeIo\PackagingTools\Markdown\Traits;

use Exception;
use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Markdown\Table;

trait MarkdownTables
{
    /**
     * read a csv file and converts it into a table
     *
     * @markdown
     *
     * @throws Exception
     */
    public function addTableFromFile(string $filepath, Filesystem $filesystem = new Filesystem): void
    {
        $this->blocks[] = (new Table)->getTableFromFile($filepath, $filesystem);
    }

    /**
     * takes a csv string and converts it into a table
     */
    public function addTableFromCsvString(string $csv, string $delimiter): void
    {
        $this->blocks[] = (new Table)->addTableFromCsvString($csv, $delimiter);
    }

    /**
     * takes an array and converts it into a table
     *
     * @markdown
     */
    public function addTableFromArray(array $data): void
    {
        $this->blocks[] = (new Table)->getTableFromArray($data);
    }
}

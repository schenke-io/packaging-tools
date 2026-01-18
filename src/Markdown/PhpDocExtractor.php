<?php

namespace SchenkeIo\PackagingTools\Markdown;

/**
 * Utility for parsing and extracting data from PHPDoc comments.
 *
 * This class provides a specialized static method to decompose raw DocBlock
 * strings into structured associative arrays. It handles the cleanup of
 * DocBlock markers (/**, *, *\/) and separates the content into
 * summaries, detailed descriptions, and annotated tags.
 *
 * Core Functionality:
 * - Line Normalization: Standardizes line endings and removes common DocBlock prefixes.
 * - Summary Extraction: Captures the first non-empty line as the documentation summary.
 * - Description Assembly: Combines subsequent non-tag lines into a multi-line description.
 * - Tag Parsing: Identifies @-prefixed tags and collects their values into named arrays.
 *
 * Methods:
 * - getFrom(): The primary static parser for converting PHPDoc strings into usable data arrays.
 */
class PhpDocExtractor
{
    /**
     * Parses the DocBlock comment.
     *
     * @param  string  $docComment  The DocBlock comment string.
     * @return array<string, string|array<int, string>>
     */
    public static function getFrom(string $docComment): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $docComment) ?: [];
        $lines = array_map(function ($line) {
            return trim((string) preg_replace('/^ *\/\*\*?| *\*\/|\s*\*/', '', $line));
        }, $lines);
        $data['summary'] = '';
        $data['description'] = '';
        $inDescription = false;

        foreach ($lines as $line) {
            if (! $line) {
                continue;
            }
            if (str_starts_with($line, '@')) {
                $parts = preg_split('/\s+/', $line, 2) ?: [];
                $tagName = strtolower(ltrim($parts[0] ?? '', '@'));
                $tagValue = isset($parts[1]) ? trim($parts[1]) : '';
                if (! isset($data[$tagName])) {
                    $data[$tagName] = [];
                }
                $data[$tagName][] = $tagValue;
                $inDescription = false;
            } elseif ($line !== '') {
                if (! $inDescription && $data['summary'] === '') {
                    $data['summary'] = $line;
                } else {
                    $data['description'] .= ($data['description'] !== '' ? "\n" : '').$line;
                    $inDescription = true;
                }
            }
        }

        return $data;
    }
}

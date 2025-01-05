<?php

namespace SchenkeIo\PackagingTools\Markdown;

class PhpDocExtractor
{
    /**
     * Parses the DocBlock comment.
     *
     * @param  string  $docComment  The DocBlock comment string.
     */
    public static function getFrom(string $docComment): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $docComment);
        $lines = array_map(function ($line) {
            return trim(preg_replace('/^ *\/\*\*?| *\*\/|\s*\*/', '', $line));
        }, $lines);
        $data['summary'] = '';
        $data['description'] = '';
        $inDescription = false;

        foreach ($lines as $line) {
            if (! $line) {
                continue;
            }
            if (str_starts_with($line, '@')) {
                $parts = preg_split('/\s+/', $line, 2);
                $tagName = strtolower(ltrim($parts[0], '@'));
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

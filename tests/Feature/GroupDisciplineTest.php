<?php

// tests/Feature/GroupDisciplineTest.php
use Illuminate\Filesystem\Filesystem;

pest()->group('arch');

it('declares 1 to 3 groups in every test file', function () {
    $fileSystem = new Filesystem;
    $offenders = collect($fileSystem->allFiles(__DIR__.'/../../tests'))
        ->filter(fn ($f) => str_ends_with($f->getFilename(), 'Test.php')
            || str_starts_with($f->getFilename(), 'test_'))
        // Skip Pest's own bootstrap
        ->reject(fn ($f) => in_array($f->getFilename(), ['TestCase.php', 'Pest.php'], true))
        ->mapWithKeys(function ($file) use ($fileSystem) {
            $contents = $fileSystem->get($file->getRealPath());

            // Count distinct group names declared at file level or per-test.
            preg_match_all(
                "/(?:->group\(|pest\(\)->group\(|uses\(\)->group\()((?:\s*['\"][^'\"]+['\"]\s*,?\s*)+)\)/",
                $contents,
                $matches
            );

            $groups = collect($matches[1] ?? [])
                ->flatMap(fn ($g) => preg_split("/\s*,\s*/", trim($g, ', ')))
                ->map(fn ($g) => trim($g, " '\""))
                ->filter()
                ->unique()
                ->values();

            return [$file->getRelativePathname() => $groups->count()];
        })
        ->reject(fn (int $count) => $count >= 1 && $count <= 3)
        ->all();

    expect($offenders)->toBe(
        [],
        "Every test file must declare 1 to 3 groups. Offenders:\n"
            .collect($offenders)->map(fn ($n, $f) => "  {$f}: {$n} groups")->implode("\n")
    );
});

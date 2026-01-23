<?php

namespace SchenkeIo\PackagingTools\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Trait to load a seeded SQL file into the database.
 *
 * This trait is intended to be used in tests where you want to bypass
 * standard migrations and seeding for performance. It should be used
 * in combination with DatabaseTransactions to ensure tests remain
 * isolated.
 */
trait LoadsSeededSql
{
    /**
     * Load the seeded SQL file into the database.
     *
     * This method bypasses RefreshDatabase and should be used with DatabaseTransactions.
     *
     * @param  string  $path  The relative path to the SQL file from the project root.
     */
    public function loadSeededSql(string $path = 'tests/Data/seeded.sql'): void
    {
        try {
            DB::table('users')->count() === 0;
        } catch (\Exception $e) {
            if (File::exists($path)) {
                DB::unprepared(File::get($path));
            }
        }
    }
}

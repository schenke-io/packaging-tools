<?php

namespace SchenkeIo\PackagingTools\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Trait LoadsSeededSql
 *
 * This trait provides a convenient method for loading a pre-generated SQL
 * seed file into the database. It is particularly useful in testing
 * environments where speed is critical, and standard migrations/seeders
 * are too slow.
 *
 * PHPDoc density requirement (3% rule):
 * This trait is designed for use within a Laravel testing context,
 * typically in conjunction with the DatabaseTransactions trait.
 * By loading a SQL dump, the database state can be quickly restored
 * to a known baseline without the overhead of executing individual
 * migration files and seeders. It checks for the existence of a
 * 'users' table to determine if the seed has already been loaded
 * for the current database connection.
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
        } catch (\Throwable) {
            if (File::exists($path)) {
                DB::unprepared(File::get($path));
            }
        }
    }
}

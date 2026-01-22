<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use SchenkeIo\PackagingTools\Traits\LoadsSeededSql;

class TraitUser
{
    use LoadsSeededSql;
}

describe('LoadsSeededSql Trait', function () {
    it('attempts to load the SQL file if it exists', function () {
        File::shouldReceive('exists')->with('tests/Data/seeded.sql')->andReturn(true);
        File::shouldReceive('get')->with('tests/Data/seeded.sql')->andReturn('SQL CONTENT');
        DB::shouldReceive('unprepared')->with('SQL CONTENT')->once();

        $user = new TraitUser;
        $user->loadSeededSql();
    });

    it('does nothing if the SQL file does not exist', function () {
        File::shouldReceive('exists')->with('tests/Data/seeded.sql')->andReturn(false);
        DB::shouldReceive('unprepared')->never();

        $user = new TraitUser;
        $user->loadSeededSql();
        expect(true)->toBeTrue();
    });
});

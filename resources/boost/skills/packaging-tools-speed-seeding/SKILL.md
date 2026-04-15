---
name: packaging-tools-speed-seeding
description: Speed up tests by loading a pre-generated SQL dump instead of running migrations
---

## Speed Seeding

### When to use this skill
Use in test suites where running full migrations and seeders on every test is too slow. Load a pre-generated SQL dump once per test run instead.

This is a companion to the `sql-cache` configuration key, which generates the dump.

### How it works

1. **Generate the dump** — `sql-cache: true` in `.packaging-tools.neon` makes `composer setup` dump the current SQLite DB to `tests/Data/seeded.sql` (configurable path).
2. **Load in tests** — the `LoadsSeededSql` trait loads that dump on first access, detected by checking if the `users` table exists.

### Usage

```php
use SchenkeIo\PackagingTools\Traits\LoadsSeededSql;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MyFeatureTest extends TestCase
{
    use DatabaseTransactions;  // wrap each test in a transaction — do NOT use RefreshDatabase
    use LoadsSeededSql;

    public function setUp(): void
    {
        parent::setUp();
        $this->loadSeededSql();           // default: tests/Data/seeded.sql
        // $this->loadSeededSql('tests/Data/custom.sql');   // custom path
    }
}
```

`loadSeededSql()` is a no-op if the `users` table already exists in the current connection, so it is safe to call in every test without redundant reloads.

### Generating the SQL dump

In `.packaging-tools.neon`:

```neon
sql-cache: true            # dumps to tests/Data/seeded.sql
# sql-cache: tests/Data/custom.sql   # custom path
# sql-cache: null          # disabled
```

To run migrations and then dump in one step, add `@sql-cache` as a step in a custom task:

```neon
customTasks:
    seed: "@sql-cache"
```

### Important

- Use `DatabaseTransactions`, **not** `RefreshDatabase`. `RefreshDatabase` re-runs migrations and undoes the seeded state.
- The SQL file must be committed to the repository so CI can load it without running seeders.
- Regenerate the dump whenever your schema or seed data changes.

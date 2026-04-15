---
name: packaging_tools_imported_migrations
description: Regenerate package migrations from a live database schema
---

## Database Migrations

### When to use this skill
Use when you want to keep your package's migration files in sync with a development database. This is a "database-first" workflow: you modify the database manually, then regenerate the migrations from it.

Requires `kitloong/laravel-migrations-generator` to be installed.

### Quick start

```bash
composer migrations
```

This runs the full migration regeneration cycle: deletes old migrations, generates new ones from the configured database connection, then cleans environment-specific connection calls from the files.

### Configuration (`.packaging-tools.neon`)

```neon
migrations: mysql:*
```

Format: `connection:tables` — use `*` to auto-detect tables from your Eloquent models.

Examples:
- `mysql:*` — use `mysql` connection, detect tables from models
- `sqlite:users,posts` — use `sqlite` connection, only those tables
- `null` — disable migration generation

### Process

1. Checks that `kitloong/laravel-migrations-generator` is installed.
2. Reads the connection and table list from `.packaging-tools.neon`.
3. If `*` is used, scans for Eloquent models in (in priority order):
   - `workbench/app/Models`
   - `app/Models`
   - `src/Models`
4. Cleans the migrations folder.
5. Runs `migrate:generate` with `--skip-log --default-index-names --date=2020-10-10`.
6. Strips environment-specific `connection()` calls from generated files.
7. Sets generated files to read-only (mode 444) to prevent manual edits.

### Workbench support

If `workbench/database/migrations` exists, it is used as the migration output path instead of `database/migrations`.

### Using the trait in an Artisan command

```php
use SchenkeIo\PackagingTools\Traits\GeneratesPackageMigrations;
use Illuminate\Console\Command;

class MyMigrationCommand extends Command
{
    use GeneratesPackageMigrations;

    public function handle(): void
    {
        $this->generatePackageMigrations();
    }
}
```

The trait must be used inside a class that extends `Illuminate\Console\Command` (it calls `$this->call()`). It reads `.packaging-tools.neon` automatically and performs the same steps as `composer migrations`.
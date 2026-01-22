
## Database Migrations

The migrations component helps you keep your package's migrations in sync with your development database. It leverages `kitloong/laravel-migrations-generator` to regenerate migrations from an existing database schema.

### Usage

Run the following command to start the migration regeneration:

```bash
composer migrations
```

### Process

1. **Check for Generator**: The tool verifies if `kitloong/laravel-migrations-generator` is installed.
2. **Connection Selection**: The tool uses the source connection configured in `.packaging-tools.neon` or defaults to your primary database connection.
3. **Cleanup**: Existing migrations in the migrations folder will be deleted to ensure a clean state.
4. **Regeneration**: New migrations are generated from the selected database connection.
5. **Permissions**: Generated migration files are set to read-only (mode 444) to prevent accidental manual edits, encouraging the "database-first" approach for packages.

### Workbench Support

If you are using a workbench for package development, the tool automatically detects and uses `workbench/database/migrations` if it exists.

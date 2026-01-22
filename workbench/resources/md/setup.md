
## Setup Command

The `setup` command is the main entry point for configuring your package development environment. It synchronizes your `composer.json` scripts and package requirements based on your declarative configuration.

### Initialization

To initialize a new project with a default configuration or sync from `composer.json`:

```bash
composer setup config
```

This ensures the `.packaging-tools.neon` file exists (creates a default one if missing) and merges any tools already present in `composer.json` that are missing from the configuration.

### Automated Configuration

Running `composer setup` without arguments reads your `.packaging-tools.neon` file and:
1. Detects required package dependencies and informs you how to add them to `composer.json`.
2. Updates the `scripts` section in `composer.json` to match your enabled tasks.
3. Synchronizes project metadata.

### Manual Commands

- **config**: `composer setup config` - Initialize or sync `.packaging-tools.neon` from `composer.json`.
- **update**: `composer setup update` - Sync script and package deltas from configuration to `composer.json`.
- **badges**: `composer setup badges` - Manually trigger badge generation for all detected metrics.
- **add**: `composer add` - Check for and add missing package dependencies.

### Workbench Integration

The `setup` command is fully aware of Laravel Workbench environments. When running in a package with `/src` and a workbench:
- It looks for `workbench/composer.json` for additional context.
- Migrations are automatically targeted at `workbench/database/migrations`.
- Artisan commands are scanned from `workbench/app/Console/Commands`.
- The `MakeMarkdown.php` script is expected at the project root by default but can be customized.

### Configuration file

All settings are stored in `.packaging-tools.neon` in your project root. This file uses the NEON format, providing a clean, schema-validated way to manage your development environment.

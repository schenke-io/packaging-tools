---
name: packaging-tools-setup
description: Install, configure, and run packaging tools via composer scripts
---

## Setup

### When to use this skill
Use when installing the package for the first time, updating the configuration, or running any of the built-in task commands (`test`, `analyse`, `coverage`, `markdown`, etc.).

### Installation

```bash
composer require schenke-io/packaging-tools
```

Add the setup script to `composer.json`:

```json
{
    "scripts": {
        "setup": "SchenkeIo\\PackagingTools\\Setup::handle"
    }
}
```

### Commands

| Command | Action |
|---|---|
| `composer setup` | Show current config status and any pending changes |
| `composer setup config` | Create or sync `.packaging-tools.neon` |
| `composer setup update` | Apply pending composer.json script additions and install missing packages |
| `composer setup badges` | Generate all auto-detected SVG badges |
| `composer setup <task>` | Run a specific configured task (e.g., `test`, `pint`, `quick`) |

### Configuration file: `.packaging-tools.neon`

```neon
analyse: true
coverage: true
infection: true
markdown: php .make-markdown.php
migrations: mysql:*
pint: true
test: pest
quick:
    - pint
    - test
    - markdown
release:
    - pint
    - analyse
    - coverage
    - infection
    - markdown
sql-cache: true
customTasks: {}
```

### Configuration keys

| Key | Type | Description |
|---|---|---|
| `analyse` | `bool` | PHPStan static analysis (auto-detects Larastan) |
| `coverage` | `bool` | Adds coverage flags to test run; requires `clover.xml` |
| `infection` | `bool` | Mutation testing via `infection/infection` |
| `markdown` | `string\|null` | Command to run the Markdown assembler script |
| `migrations` | `string\|null` | Migration regeneration: format `connection:tables` or `connection:*` |
| `pint` | `bool` | Code style with Laravel Pint |
| `test` | `string` | Test runner: `pest`, `phpunit`, or `''` to disable |
| `quick` | `array` | Task group for fast iteration (default: pint, test, markdown) |
| `release` | `array` | Task group for pre-release checks |
| `sql-cache` | `bool\|string\|null` | Dump SQLite DB to SQL file; `true` = `tests/Data/seeded.sql` |
| `customTasks` | `array` | Map of custom task names to shell commands or class names |

### Custom tasks

```neon
customTasks:
    lint: php -l src/
    my-task: App\Console\Commands\MyTask
```

Run with: `composer setup lint` or `composer setup my-task`.

### Concept

- Configuration is the single source of truth — editing `.packaging-tools.neon` controls everything.
- Manual edits to `composer.json` scripts are preserved; the tool warns before overwriting.
- `composer setup` (no args) shows a diff of what would change — it never modifies without an explicit subcommand.
- All keys define their own schema via `nette/schema`; invalid config produces a clear error with a "did you mean?" suggestion.

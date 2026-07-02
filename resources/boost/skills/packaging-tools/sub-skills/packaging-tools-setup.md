---
name: packaging-tools-setup
type: Agent Skill
title: packaging-tools-setup
description: Install, configure, and run packaging tools via composer scripts
timestamp: 2026-06-29
---

# Setup

## Purpose
Simplify the installation and configuration of packaging tools, providing a single source of truth via `.packaging-tools.neon`.

## When to Use
Use when installing the package for the first time, updating the configuration, or running any of the built-in task commands (`test`, `analyse`, `coverage`, `markdown`, etc.).

## Installation

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

## Commands

| Command | Action |
|---|---|
| `composer pack-to` | Show current config status and any pending changes |
| `composer pack-to config` | Create or sync `.packaging-tools.neon` |
| `composer pack-to update` | Apply pending composer.json script additions and install missing packages |
| `composer pack-to badges` | Generate all auto-detected SVG badges |
| `composer pack-to <task>` | Run a specific configured task (e.g., `test`, `pint`, `quick`) |

## Configuration file: `.packaging-tools.neon`

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

## Configuration keys

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

## Custom tasks

```neon
customTasks:
    lint: php -l src/
    my-task: App\Console\Commands\MyTask
```

Run with: `composer pack-to lint` or `composer pack-to my-task`.

## Concept

- Configuration is the single source of truth — editing `.packaging-tools.neon` controls everything.
- Manual edits to `composer.json` scripts are preserved; the tool warns before overwriting.
- `composer pack-to` (no args) shows a diff of what would change — it never modifies without an explicit subcommand.
- All keys define their own schema via `nette/schema`; invalid config produces a clear error with a "did you mean?" suggestion.

---
name: packaging-tools-guidelines
description: Write AI guidelines and skills for projects based on Laravel Boost standards
---

## AI Guidelines and Skills for Boost

### When to use this skill
Use when creating or updating AI guidelines (`core.blade.php`) or skill files (`SKILL.md`) for a Laravel package so that Boost-compatible projects can load them automatically.

### AI Guidelines

Add a `resources/boost/guidelines/core.blade.php` file to your package. When users run `php artisan boost:install`, Boost loads it automatically as AI context.

Guidelines should:
- Briefly describe what the package does
- List key conventions and file structures
- Show example commands and code snippets
- Be concise and actionable — written for AI, not humans

#### Example `core.blade.php`

```php
## vendor/package-name

This package provides [brief description].

### Features

- Feature 1: [description].
- Feature 2: [description]. Example:

@verbatim
<code-snippet name="How to use Feature 2" lang="php">
$result = PackageName::featureTwo($param1, $param2);
</code-snippet>
@endverbatim
```

The `@verbatim` / `<code-snippet>` wrapper is processed by the Skills assembler into a plain fenced code block when included in Markdown output.

### AI Skills

Add a `resources/boost/skills/{skill-name}/SKILL.md` file. Required frontmatter: `name` and `description`. The folder name is used as the skill identifier.

#### Example `SKILL.md`

```markdown
---
name: package-name-feature
description: Build and work with PackageName features.
---

## When to use this skill
Use when working with PackageName features...

## Features

- Feature 1: description.
- Feature 2: description. Example:

$result = PackageName::featureTwo($param1, $param2);
```

### Including skills in Markdown assembly

The `MarkdownAssembler` can embed skill content or render a summary table:

```php
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;

$assembler = new MarkdownAssembler('resources/md');

// embed full content of all skills
$assembler->skills()->all();

// embed a specific skill
$assembler->skills()->add('my-skill-name');

// render a summary table (name + description) for all skills
$assembler->skillOverview();
```

`skillOverview()` generates a Markdown table with linked skill names and their descriptions, sourced from each SKILL.md's frontmatter.

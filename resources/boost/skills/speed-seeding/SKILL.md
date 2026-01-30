---
name: Turbo Speed Seeding
description: Speeds up database preparation in tests by loading a pre-generated SQL dump
---

## Seeding Trait

This trait is designed for testing environments to speed up database preparation. Instead of running all migrations and seeders for every test, you can load a pre-generated SQL dump.

### Usage

```php
use SchenkeIo\PackagingTools\Traits\LoadsSeededSql;
use Tests\TestCase;

class MyFeatureTest extends TestCase
{
    use LoadsSeededSql;

    public function setUp(): void
    {
        parent::setUp();
        $this->loadSeededSql();
    }
}
```

- **Speed:** Significantly faster than standard migrations and seeders in CI environments.
- **Ease of use:** Simply call `loadSeededSql()` in your test's `setUp` method.
- **Smart Loading:** It checks if the database is already seeded (e.g., by checking for the `users` table) before loading the SQL file to avoid redundant operations.

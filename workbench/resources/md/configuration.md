
## Configuration

The package is configured via a `.packaging-tools.neon` file in your project root. This file uses the [NEON](https://ne-on.org/) format, which is similar to YAML but provides stronger schema validation and is ideal for configuration.

### Initialization

To create or sync your configuration file:

```bash
composer setup config
```

### Configuration Keys

The following keys are supported in `.packaging-tools.neon`:

| Key | Type | Purpose | Example |
|---|---|---|---|
| `analyse` | `bool` | Enables PHPStan static analysis | `analyse: true` |
| `coverage` | `bool` | Enables code coverage reporting during tests | `coverage: true` |
| `infection` | `bool` | Enables mutation testing with Infection | `infection: true` |
| `markdown` | `string\|null` | The command to run for Markdown assembly | `markdown: php workbench/MakeMarkdown.php` |
| `migrations` | `string\|null` | Configuration for migration generation | `migrations: mysql:users,posts` |
| `pint` | `bool` | Enables code styling with Laravel Pint | `pint: true` |
| `quick` | `array` | Group task: `pint`, `test`, `markdown` | `quick: [pint, test, markdown]` |
| `release` | `array` | Group task for pre-release checks | `release: [pint, analyse, coverage, markdown]` |
| `test` | `string` | Test runner: `pest`, `phpunit` or `''` | `test: pest` |
| `customTasks`| `array` | Mapping of custom task names to commands | `customTasks: { my-task: "ls -la" }` |

### Detailed Key Purpose

#### `analyse`
Runs PHPStan to perform static analysis on your codebase. It automatically detects if you are using standard PHP or Laravel (Larastan).

#### `coverage`
Requires a test runner to be configured. It adds coverage flags to the test command and checks for the existence of `clover.xml`.

#### `infection`
Runs mutation testing to check the quality of your tests. Requires `infection/infection` to be installed.

#### `markdown`
Points to the script that assembles your documentation. Usually `php workbench/MakeMarkdown.php`. Use `null` to disable.

#### `migrations`
Uses `kitloong/laravel-migrations-generator`. Can be a string in the format `connection:table1,table2`. Use `null` to disable.

#### `pint`
Uses Laravel Pint to ensure your code follows the project's styling rules.

#### `quick`
A shortcut to run essential checks quickly. By default it runs `pint`, `test` and `markdown`. You can override the list of tasks by providing an array.

#### `release`
A comprehensive check before releasing a new version. It typically runs `pint`, `analyse`, `test`, `coverage`, `infection` and `markdown`.

#### `test`
Selects the testing framework. Supported values are `pest` and `phpunit`. Use an empty string `''` to disable tests.

#### `customTasks`
Allows you to define your own tasks that can be run via `composer setup <task-name>`.

### Schema Validation

All tasks in `packaging-tools` define their own configuration schema using `nette/schema`. This ensures that your configuration is always valid and provides helpful error messages if something is misconfigured.

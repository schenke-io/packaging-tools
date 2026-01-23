

This package is a collection of tools to simplify the package and project development.

The main elements are:
- **Markdown** Assemble the readme.md file out of small markdown files, class comments and other sources
- **Badge** build the badge custom or from existing files
- **Setup** read the `.packaging-tools.neon` configuration file and modify scripts in `composer.json`

### Basics

#### GeneratesPackageMigrations
This trait allows your package to easily regenerate its own migrations from the current database schema, ensuring your package's migrations are always in sync with your development environment.

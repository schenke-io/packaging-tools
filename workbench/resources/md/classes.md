
## Classes

The tool can automatically document your package classes by parsing their PHPDoc blocks.

### Automatic Documentation

When using the `MarkdownAssembler`, you can include documentation for all your classes with:

```php
$assembler->classes()->all();
```

This will:
1. Scan your source directory (detected automatically as `src/` or `app/`).
2. Parse the class-level PHPDoc of each class.
3. Generate a formatted list of classes with their descriptions and key methods.

### Best Practices for Class Documentation

To get the most out of this feature, ensure your classes have a comprehensive PHPDoc block:

```php
/**
 * Short description of the class
 *
 * Longer description explaining the purpose of the class
 * and how it should be used within the package.
 */
class MyClass { ... }
```

The assembler looks for at least a few lines of description to ensure quality documentation.


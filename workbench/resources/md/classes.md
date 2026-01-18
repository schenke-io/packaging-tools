
## Classes

The tool can automatically document your package classes by parsing their PHPDoc blocks.

### Automatic Documentation

When using the `MarkdownAssembler`, you can include documentation for all your classes with:

```php
$assembler->classes()->all();

// or document a single class:
$assembler->classes()->add(MyClass::class);
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

### Using @markdown for Extended Documentation

Sometimes, class or method documentation is too extensive for a single PHPDoc block. You can use the `@markdown` tag to include external Markdown files into your class documentation.

#### Class-level @markdown

If you add `@markdown` to your class-level PHPDoc, the tool will look for a Markdown file named after the class's namespace path.

- **Convention:** The path is formed by taking the full namespace, excluding the first two components (usually the vendor and package name), and appending `.md`.
- **Example:** `SchenkeIo\PackagingTools\Markdown\MarkdownAssembler` becomes `Markdown/MarkdownAssembler.md`.
- **Base Directory:** These paths are relative to the markdown source directory passed to the `MarkdownAssembler` constructor.

```php
/**
 * Class Summary
 *
 * @markdown
 */
class MarkdownAssembler { ... }
```

#### Method-level @markdown

Similarly, you can use `@markdown` on individual public methods.

- **Convention:** The tool looks for a `.md` file in a subdirectory named after the class (using the same convention as above).
- **Example:** `MarkdownAssembler::init()` will look for `Markdown/MarkdownAssembler/init.md`.

```php
/**
 * Method Summary
 *
 * @markdown
 */
public function init() { ... }
```


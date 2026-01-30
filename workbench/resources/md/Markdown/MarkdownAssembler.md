

#### How to assemble a markdown

To assemble a markdown you need:
- a directory with markdown source files (e.g., `workbench/resources/md`)
- an assembly script (e.g., `workbench/MakeMarkdown.php`)

The `MarkdownAssembler` helps you combine static markdown files with dynamically generated content like badges, tables, and class documentation.

##### Bootstrapping

You can initialize a markdown directory with standard files:

```php
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;

MarkdownAssembler::init('workbench/resources/md');
```

##### Assembly Example

```php
<?php

require "vendor/autoload.php";

use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;

try {
    $mda = new MarkdownAssembler('workbench/resources/md');

    // add a header with project name, description and badges
    $mda->autoHeader('My Awesome Package');

    // include a static file from the markdown directory
    $mda->addMarkdown("introduction.md");

    // add a Table of Contents for all headings in the final document
    $mda->toc();

    // add a table with all skills, their descriptions and links
    $mda->skillOverview();

    // add all skills from resources/boost/skills/
    $mda->skills()->all();

    // add a table from a CSV file
    $mda->tables()->fromFile('data.csv');

    // add documentation for all classes in src/
    $mda->classes()->all();

    // or from a single class
    $mda->classes()->add(MarkdownAssembler::class);

    // write the result to a file (relative to root directory)
    $mda->writeMarkdown("README.md");

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
```

##### Key Methods

- `autoHeader(?string $title)`: Adds project title, description, and common badges.
- `addMarkdown(string $filename)`: Includes a file from the source directory.
- `addText(string $text)`: Appends raw markdown text.
- `toc()`: Inserts a Table of Contents.
- `skillOverview()`: Adds a table with all skills, their descriptions and links.
- `skills()`: Accesses the Skills piece for including feature documentation.
- `tables()`: Accesses the Tables piece for creating markdown tables from arrays, CSV strings, or files.
- `classes()`: Accesses the Classes piece for generating documentation from PHP classes using reflection.
- `badges()`: Accesses the Badges piece for adding custom badges.
- `writeMarkdown(string $filename)`: Finalizes and writes the assembled markdown to the specified path.


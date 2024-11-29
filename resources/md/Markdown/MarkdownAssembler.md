

### How to assemble a markdown

To assemble a markdown you need these things:
- a directory with well named markdown files
- documentation of classes and methods
- csv files for tables 
- a script
  - which writes badges
  - which read and assemble these files

This script can be a script run by php itself or 
a class file with a static method.




```php
<?php


require "vendor/autoload.php";

use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;

/*
 * this scripts make the package itself and tests its functionality
 */

try {
    $mda = new MarkdownAssembler(/* subdirectory for markdown include files */);
    $mda->addMarkdown(/* relative to markdown directory */);
    $mda->addTableOfContents();
    // relative to markdown directory
    $mda->addMarkdown("installation.md");
    // makes markdown from a class phpdoc
    $mda->addClassMarkdown(MarkdownAssembler::class);

    // path relative to root directory
    $mda->writeMarkdown("README.md");
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}




```


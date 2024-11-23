
## Installation

Install the package with composer:

```php
composer require schenke-io/packaging-tools
```

Use it as part of your package development.

```php
<?php


require "vendor/autoload.php";

use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;

/*
 * this scripts make the package itself and tests its functionality
 */

try {
    $mda = new MarkdownAssembler(
        /* root directory of the project */, 
        /* subdirectory for markdown include files */
    );
    $mda->addMarkdown(/* relative */);
    $mda->addTableOfContents();
    $mda->addMarkdown("workbench/resources/md/installation.md");
    $mda->addClassMarkdown(MarkdownAssembler::class);


    $mda->writeMarkdown("README.md");
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}




```

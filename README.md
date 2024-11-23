<!--

This file was written by 'make_myself.php' line 13 using
SchenkeIo\PackagingTools\Markdown\MarkdownAssembler

Do not edit manually as it will be overwritten.

-->

# Packaging Tools

[![Latest Version on Packagist](https://img.shields.io/packagist/v/schenke-io/packaging-tools?style=plastic)](https://packagist.org/packages/schenke-io/packaging-tools)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/schenke-io/packaging-tools/run-tests.yml?branch=main&label=tests&style=plastic)](https://github.com/schenke-io/packaging-tools/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/schenke-io/packaging-tools.svg?style=plastic)](https://packagist.org/packages/schenke-io/packaging-tools)
![](/.github/coverage-badge.svg)


![](/.github/werkstatt.png)





* [Packaging Tools](#packaging-tools)
  * [Installation](#installation)
    * [MarkdownAssembler](#markdownassembler)




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
        /* subdirectory of root for markdown include files */
    );
    $mda->addMarkdown(/* relative */);
    $mda->addTableOfContents();
    $mda->addMarkdown(/* relative */);
    $mda->addMarkdown(/* relative */);
    $mda->addMarkdown(/* relative */);
    $mda->addClassMarkdown(MarkdownAssembler::class);

    $mda->addTableFromFile(/* csv file results in a table */);

    $mda->writeMarkdown("README.md");
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}




```



### MarkdownAssembler

Assembler of a markdown file

Explanation markdown assembler




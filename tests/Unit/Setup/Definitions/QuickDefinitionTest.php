<?php

use SchenkeIo\PackagingTools\Setup\Definitions\QuickDefinition;

test('QuickDefinition constructor initializes with correct values', function () {
    $quickDefinition = new QuickDefinition;
    expect($quickDefinition->getTasks())->toBe(['pint', 'test', 'markdown']);
});

test('QuickDefinition explainConfig returns correct text', function () {
    $quickDefinition = new QuickDefinition;
    expect($quickDefinition->explainConfig())->toBe('an array of scripts to include in this group: pint, test, markdown');
});

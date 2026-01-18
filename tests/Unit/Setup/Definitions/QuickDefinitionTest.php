<?php

use SchenkeIo\PackagingTools\Setup\Definitions\QuickDefinition;

test('QuickDefinition constructor initializes with correct values', function () {
    $quickDefinition = new QuickDefinition;
    expect($quickDefinition->getTasks())->toBe(['pint', 'test', 'markdown']);
});

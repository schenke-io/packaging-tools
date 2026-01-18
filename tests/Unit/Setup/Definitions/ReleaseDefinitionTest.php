<?php

use SchenkeIo\PackagingTools\Setup\Definitions\ReleaseDefinition;

test('ReleaseDefinition constructor initializes with correct groups', function () {
    $releaseDefinition = new ReleaseDefinition;
    expect($releaseDefinition->getTasks())->toBe(['pint', 'analyse', 'test', 'coverage', 'infection', 'markdown']);
});

test('ReleaseDefinition extends GroupDefinition', function () {
    $releaseDefinition = new ReleaseDefinition;
    expect($releaseDefinition)->toBeInstanceOf(SchenkeIo\PackagingTools\Setup\Definitions\GroupDefinition::class);
});

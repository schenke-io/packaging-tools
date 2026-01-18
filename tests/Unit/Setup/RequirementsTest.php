<?php

use SchenkeIo\PackagingTools\Setup\Requirements;

it('can add requirements', function () {
    $req = new Requirements;
    $req->addRequire('package/a');
    $req->addRequireDev('package/b');

    expect($req->data())->toBe([
        'require' => ['package/a'],
        'require-dev' => ['package/b'],
    ]);
});

it('can create dev requirement', function () {
    $req = Requirements::dev('package/b');
    expect($req->data())->toBe(['require-dev' => ['package/b']]);
});

it('can create require requirement', function () {
    $req = Requirements::require('package/a');
    expect($req->data())->toBe(['require' => ['package/a']]);
});

it('can merge requirements', function () {
    $req1 = Requirements::require('package/a');
    $req2 = Requirements::dev('package/b');
    $req1->addRequirements($req2);

    expect($req1->data())->toBe([
        'require' => ['package/a'],
        'require-dev' => ['package/b'],
    ]);
});

it('can remove requirements', function () {
    $req = Requirements::require('package/a');
    $req->addRequireDev('package/b');
    $req->removeRequire('package/a');
    $req->removeRequire('package/b');

    expect($req->data())->toBe([
        'require' => [],
        'require-dev' => [],
    ]);
});

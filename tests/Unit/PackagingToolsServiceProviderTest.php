<?php

namespace SchenkeIo\PackagingTools\Tests\Unit;

use Illuminate\Contracts\Foundation\Application;
use Mockery;
use SchenkeIo\PackagingTools\PackagingToolsServiceProvider;

it('can instantiate the service provider', function () {
    $app = Mockery::mock(Application::class);
    $provider = new PackagingToolsServiceProvider($app);
    expect($provider)->toBeInstanceOf(PackagingToolsServiceProvider::class);
});

it('can call register and boot', function () {
    $app = Mockery::mock(Application::class);
    $provider = new PackagingToolsServiceProvider($app);
    $provider->register();
    $provider->boot();
    expect(true)->toBeTrue();
});

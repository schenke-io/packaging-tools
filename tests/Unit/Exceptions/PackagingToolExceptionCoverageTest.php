<?php

namespace Tests\Unit\Exceptions;

use SchenkeIo\PackagingTools\Exceptions\PackagingToolException;

it('can create privateRepositoryNotSupported exception', function () {
    $exception = PackagingToolException::privateRepositoryNotSupported('test/project');
    expect($exception->getMessage())->toContain("Private repository 'test/project' is not supported for public badges");
});

it('can create pathDetectionFailed exception', function () {
    $exception = PackagingToolException::pathDetectionFailed('test');
    expect($exception->getMessage())->toContain('Could not detect path for badge type: test');
});

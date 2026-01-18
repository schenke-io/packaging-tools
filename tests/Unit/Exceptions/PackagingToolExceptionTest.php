<?php

use SchenkeIo\PackagingTools\Exceptions\PackagingToolException;

it('throws configError', function () {
    $e = PackagingToolException::configError('file', 'msg');
    expect($e->getMessage())->toBe("Configuration error in 'file': msg");
});

it('throws invalidConfigFile', function () {
    $e = PackagingToolException::invalidConfigFile('file', 'msg');
    expect($e->getMessage())->toBe("Invalid configuration file 'file': msg");
});

it('throws composerJsonNotFound', function () {
    $e = PackagingToolException::composerJsonNotFound('path');
    expect($e->getMessage())->toBe('composer.json not found: path');
});

it('throws invalidComposerJson', function () {
    $e = PackagingToolException::invalidComposerJson('msg');
    expect($e->getMessage())->toBe('Invalid composer.json: msg');
});

it('throws fileNotFound', function () {
    $e = PackagingToolException::fileNotFound('path');
    expect($e->getMessage())->toBe('File does not exist at path path.');
});

it('throws unsupportedFileExtension', function () {
    $e = PackagingToolException::unsupportedFileExtension('ext');
    expect($e->getMessage())->toBe("File extension 'ext' not supported");
});

it('throws projectRootNotFound', function () {
    $e = PackagingToolException::projectRootNotFound('path');
    expect($e->getMessage())->toBe('Project root is not a directory: path');
});

it('throws projectRootNotSet', function () {
    $e = PackagingToolException::projectRootNotSet();
    expect($e->getMessage())->toBe('Project root is not set');
});

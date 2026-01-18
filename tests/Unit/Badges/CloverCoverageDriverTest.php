<?php

use Illuminate\Filesystem\Filesystem;
use SchenkeIo\PackagingTools\Badges\Drivers\CloverCoverageDriver;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

it('extracts project-wide coverage instead of the first file', function () {
    $cloverXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<coverage>
  <project name="Test">
    <package name="P1">
      <file name="F1.php">
        <metrics statements="10" coveredstatements="10"/>
      </file>
    </package>
    <metrics statements="100" coveredstatements="50"/>
  </project>
</coverage>
XML;

    $filesystem = mock(Filesystem::class);
    $filesystem->shouldReceive('isDirectory')->andReturn(true);
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('get')->with(Mockery::pattern('/composer\.json$/'))->andReturn(json_encode(['name' => 'a/b']));

    $projectContext = new ProjectContext($filesystem, '/root');

    $filesystem->shouldReceive('get')->with('/root/clover.xml')->andReturn($cloverXml);

    $driver = new CloverCoverageDriver;
    // Current implementation will match the first one: 10/10 -> 100%
    // We want it to match the last one (project-wide): 50/100 -> 50%
    expect($driver->getStatus($projectContext, 'clover.xml'))->toBe('50%');
});

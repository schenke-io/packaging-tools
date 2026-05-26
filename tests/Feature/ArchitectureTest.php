<?php

namespace Tests\Feature;

pest()->group('feature');

arch()
    ->expect('SchenkeIo\PackagingTools')
    ->not->toUse(['die', 'dd', 'dump', 'exit']);

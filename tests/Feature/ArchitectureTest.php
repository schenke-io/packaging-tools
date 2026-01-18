<?php

namespace Tests\Feature;

arch()
    ->expect('SchenkeIo\PackagingTools')
    ->not->toUse(['die', 'dd', 'dump', 'exit']);

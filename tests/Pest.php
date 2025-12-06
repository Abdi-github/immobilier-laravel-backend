<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SeedsRolesAndPermissions;

pest()->extends(TestCase::class)
    ->use(RefreshDatabase::class, SeedsRolesAndPermissions::class)
    ->beforeEach(function () {
        $this->seedRolesAndPermissions();
    })
    ->in('Feature');

pest()->extends(TestCase::class)
    ->in('Unit');

<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| Feature tests extend Tests\TestCase and run against the PostgreSQL
| test database wonderpool_test (phpunit.xml) via RefreshDatabase. Unit tests are plain PHP.
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

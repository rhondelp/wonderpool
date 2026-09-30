<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| Feature tests extend Tests\TestCase and run against a fresh in-memory
| SQLite database (phpunit.xml) via RefreshDatabase. Unit tests are plain PHP.
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

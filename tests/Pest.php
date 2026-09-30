<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| Feature tests extend Tests\TestCase. Add RefreshDatabase per test file (or here)
| once migrations exist (M1). Tests use in-memory SQLite (phpunit.xml).
*/

pest()->extend(Tests\TestCase::class)
    ->in('Feature');

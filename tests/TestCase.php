<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base test case for all Feature tests (bound in tests/Pest.php).
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Disable Vite so views render without a built manifest (CI / fresh clones).
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}

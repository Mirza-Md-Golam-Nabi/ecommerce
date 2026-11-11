<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Feature tests render pages without built Vite assets.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}

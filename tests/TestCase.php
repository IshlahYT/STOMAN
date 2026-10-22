<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Set up the test environment.
     * withoutVite() prevents ViteManifestNotFoundException in all tests
     * when public/build/manifest.json doesn't exist (i.e., no npm run build).
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}

<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('testing', app()->environment(), 'Feature tests must use Laravel testing mode.');
        $this->assertSame('sqlite', config('database.default'), 'Feature tests must never use the application MySQL database.');
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }
}

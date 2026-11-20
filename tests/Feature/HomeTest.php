<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_is_available(): void
    {
        $this->withoutVite();

        $this->get('/')->assertOk();
    }
}

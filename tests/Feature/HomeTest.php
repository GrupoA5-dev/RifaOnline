<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeTest extends TestCase
{
    public function test_homepage_is_available(): void
    {
        $this->get('/')->assertOk();
    }
}

<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ServiceStatusTest extends TestCase
{
    public function test_home_renders_status_page_with_every_service(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Status')
                ->has('services', 4)
                ->where('services.0.name', 'PostgreSQL'));
    }
}

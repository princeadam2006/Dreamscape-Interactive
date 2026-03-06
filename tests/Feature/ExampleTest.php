<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_guests_are_redirected_to_filament_login_from_dashboard_route(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_filament_login_page_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_notifications_table_route_is_not_registered(): void
    {
        $response = $this->get('/notifications');

        $response->assertNotFound();
    }
}

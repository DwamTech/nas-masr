<?php

namespace Tests\Feature\Guest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

abstract class GuestTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run test data seeder to populate basic data
        $this->artisan('db:seed', ['--class' => 'TestDataSeeder']);
    }

    /**
     * Perform a GET request as a guest user
     */
    protected function guestGet(string $uri, array $headers = []): TestResponse
    {
        return $this->getJson($uri, $headers);
    }

    /**
     * Perform a POST request as a guest user
     */
    protected function guestPost(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->postJson($uri, $data, $headers);
    }
}

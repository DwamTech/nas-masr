<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Models\Category;

class GetProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_profile_requires_authentication(): void
    {
        $response = $this->getJson('/api/get-profile');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    public function test_get_profile_returns_authenticated_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'phone' => '01000000001',
            'password' => 'password',
            'role' => 'user',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/get-profile');

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Profile fetched successfully');
        $response->assertJsonPath('data.id', $user->id);
        $response->assertJsonPath('data.phone', $user->phone);
    }

    public function test_post_listings_requires_auth(): void
    {
        Category::create([
            'slug' => 'animals',
            'name' => 'Animals',
            'is_active' => true,
        ]);

        $resp = $this->postJson('/api/v1/animals/listings', []);
        $resp->assertStatus(401);
        $resp->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_post_listings_with_bearer_token_succeeds_or_validates(): void
    {
        Category::create([
            'slug' => 'animals',
            'name' => 'Animals',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Lister',
            'phone' => '01000000004',
            'password' => 'password',
            'role' => 'user',
        ]);

        $token = $user->createToken('listings')->plainTextToken;

        $resp = $this->postJson('/api/v1/animals/listings', [
            'title' => 'Test Listing',
        ], [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        $this->assertTrue(in_array($resp->status(), [201, 422], true));
    }

    public function test_get_profile_with_bearer_token_succeeds(): void
    {
        $user = User::create([
            'name' => 'Bearer User',
            'phone' => '01000000002',
            'password' => 'password',
            'role' => 'user',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->getJson('/api/get-profile', [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $user->id);
    }

    public function test_get_profile_with_partial_token_fails(): void
    {
        $user = User::create([
            'name' => 'Partial Token User',
            'phone' => '01000000003',
            'password' => 'password',
            'role' => 'user',
        ]);

        $full = $user->createToken('test_token')->plainTextToken;
        $parts = explode('|', $full);
        $partial = end($parts);

        $response = $this->getJson('/api/get-profile', [
            'Authorization' => 'Bearer ' . $partial,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }
}
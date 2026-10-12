<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_register_and_login(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Developer',
            'email' => 'newdev@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_user_can_like_and_unlike_tool(): void
    {
        $user = User::first();
        $tool = Project::where('status', 'published')->first();
        $initialLikes = $tool->likes_count;

        // Like
        $response = $this->actingAs($user)->post("/tools/{$tool->slug}/like");
        $tool->refresh();
        $this->assertEquals($initialLikes + 1, $tool->likes_count);

        // Unlike
        $response = $this->actingAs($user)->post("/tools/{$tool->slug}/like");
        $tool->refresh();
        $this->assertEquals($initialLikes, $tool->likes_count);
    }

    public function test_user_can_fork_tool(): void
    {
        $user = User::first();
        $source = Project::where('status', 'published')->first();

        $response = $this->actingAs($user)->post("/tools/{$source->slug}/fork");
        $response->assertRedirect();

        $forked = Project::where('user_id', $user->id)
            ->where('fork_of_id', $source->id)
            ->first();

        $this->assertNotNull($forked);
        $this->assertTrue($forked->files()->where('path', 'index.html')->exists());
    }
}

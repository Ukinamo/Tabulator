<?php

namespace Tests\Feature\Api;

use App\Models\Contestant;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CrudOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private function createSuperAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'created_by' => null,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = $this->createSuperAdmin();
    }

    /** @test */
    public function users_crud(): void
    {
        Sanctum::actingAs($this->superAdmin);

        // List
        $r = $this->getJson('/api/v1/admin/users');
        $r->assertOk();
        $r->assertJsonPath('success', true);
        $this->assertIsArray($r->json('data'));

        // Create
        $r = $this->postJson('/api/v1/admin/users', [
            'name' => 'New Judge',
            'email' => 'judge2@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
        $r->assertCreated();
        $r->assertJsonPath('data.name', 'New Judge');
        $r->assertJsonPath('data.email', 'judge2@test.com');
        $userId = $r->json('data.id');

        // Show
        $r = $this->getJson("/api/v1/admin/users/{$userId}");
        $r->assertOk();
        $r->assertJsonPath('data.id', $userId);

        // Update
        $r = $this->putJson("/api/v1/admin/users/{$userId}", [
            'name' => 'Updated Judge',
            'is_active' => true,
        ]);
        $r->assertOk();
        $r->assertJsonPath('data.name', 'Updated Judge');

        // Delete (other user)
        $r = $this->deleteJson("/api/v1/admin/users/{$userId}");
        $r->assertNoContent();

        // Cannot delete self
        $r = $this->deleteJson('/api/v1/admin/users/'.$this->superAdmin->id);
        $r->assertStatus(403);
    }

    /** @test */
    public function events_crud(): void
    {
        Sanctum::actingAs($this->superAdmin);

        // List
        $r = $this->getJson('/api/v1/admin/events');
        $r->assertOk();
        $this->assertIsArray($r->json('data'));

        // Create
        $r = $this->postJson('/api/v1/admin/events', [
            'name' => 'Test Event',
            'description' => 'Desc',
            'venue' => 'Venue A',
            'event_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => 'setup',
        ]);
        $r->assertCreated();
        $r->assertJsonPath('data.name', 'Test Event');
        $eventId = $r->json('data.id');

        // Show
        $r = $this->getJson("/api/v1/admin/events/{$eventId}");
        $r->assertOk();

        // Update
        $r = $this->putJson("/api/v1/admin/events/{$eventId}", [
            'name' => 'Updated Event',
        ]);
        $r->assertOk();
        $r->assertJsonPath('data.name', 'Updated Event');

        // Destroy returns 403
        $r = $this->deleteJson("/api/v1/admin/events/{$eventId}");
        $r->assertStatus(403);
    }

    /** @test */
    public function contestants_crud(): void
    {
        $event = Event::create([
            'name' => 'Event',
            'event_date' => now(),
            'status' => 'setup',
            'created_by' => $this->superAdmin->id,
        ]);

        Sanctum::actingAs($this->superAdmin);

        // List
        $r = $this->getJson("/api/v1/admin/events/{$event->id}/contestants");
        $r->assertOk();
        $this->assertIsArray($r->json('data'));

        // Create
        $r = $this->postJson("/api/v1/admin/events/{$event->id}/contestants", [
            'contestant_number' => '01',
            'name' => 'Contestant One',
            'bio' => null,
            'photo_url' => null,
            'is_active' => true,
        ]);
        $r->assertCreated();
        $r->assertJsonPath('data.name', 'Contestant One');
        $contestantId = $r->json('data.id');

        // Show
        $r = $this->getJson("/api/v1/admin/events/{$event->id}/contestants/{$contestantId}");
        $r->assertOk();

        // Update
        $r = $this->putJson("/api/v1/admin/events/{$event->id}/contestants/{$contestantId}", [
            'name' => 'Updated Contestant',
        ]);
        $r->assertOk();
        $r->assertJsonPath('data.name', 'Updated Contestant');

        // Delete
        $r = $this->deleteJson("/api/v1/admin/events/{$event->id}/contestants/{$contestantId}");
        $r->assertNoContent();

        $this->assertNull(Contestant::find($contestantId));
    }
}

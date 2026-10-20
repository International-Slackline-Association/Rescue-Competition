<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGrantCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_grants_admin_by_email_or_isa_id(): void
    {
        $byEmail = User::factory()->create(['email' => 'lia@example.org']);
        $byIsaId = User::factory()->create();

        $this->artisan('admin:grant', ['person' => 'Lia@Example.org'])->assertSuccessful();
        $this->artisan('admin:grant', ['person' => strtolower($byIsaId->isa_id)])->assertSuccessful();

        $this->assertTrue($byEmail->fresh()->is_admin);
        $this->assertTrue($byIsaId->fresh()->is_admin);
    }

    public function test_fails_for_unknown_people(): void
    {
        $this->artisan('admin:grant', ['person' => 'nobody@example.org'])
            ->expectsOutputToContain('need to log in once first')
            ->assertFailed();
    }
}

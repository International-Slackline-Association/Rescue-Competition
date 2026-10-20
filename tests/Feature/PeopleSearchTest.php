<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_search_finds_isa_accounts_but_not_email_only_placeholders(): void
    {
        $member = User::factory()->create(['name' => 'Kim Ropes']);
        User::factory()->placeholder()->create(['isa_id' => null, 'name' => 'kim', 'email' => 'kim@example.org']);

        $this->actingAs($member)
            ->getJson(route('people.search', ['q' => 'kim']))
            ->assertOk()
            ->assertExactJson([['value' => $member->isa_id, 'label' => 'Kim Ropes']]);
    }

    public function test_email_only_placeholder_is_found_by_exact_email(): void
    {
        User::factory()->placeholder()->create(['isa_id' => null, 'name' => 'kim', 'email' => 'kim@example.org']);

        $this->actingAs(User::factory()->create())
            ->getJson(route('people.search', ['q' => 'Kim@Example.org']))
            ->assertExactJson([['value' => 'kim@example.org', 'label' => 'kim']]);
    }

    public function test_guests_cannot_search_people(): void
    {
        $this->getJson(route('people.search', ['q' => 'kim']))->assertUnauthorized();
    }
}

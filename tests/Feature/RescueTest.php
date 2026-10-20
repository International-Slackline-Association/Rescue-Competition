<?php

namespace Tests\Feature;

use App\Enums\RescueRole;
use App\Models\Rescue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RescueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_guests_must_log_in_to_submit(): void
    {
        $this->get(route('rescues.create'))->assertRedirect(route('login'));
    }

    public function test_user_submits_a_rescue_with_known_and_new_people(): void
    {
        $me = User::factory()->create();
        $friend = User::factory()->create();

        $this->actingAs($me)->post(route('rescues.store'), [
            'happened_on' => '2026-09-01',
            'location' => 'Lake Garda',
            'image' => UploadedFile::fake()->image('proof.jpg'),
            'participants' => [
                ['person' => $me->isa_id, 'role' => 'line_rescuer'],
                ['person' => $friend->isa_id, 'role' => 'ground_rescuer'],
                ['person' => 'isa_0000abcd', 'role' => 'victim'],
                ['person' => 'Newbie@Example.org', 'role' => 'ground_rescuer'],
            ],
        ])->assertRedirect();

        $rescue = Rescue::sole();
        Storage::disk('public')->assertExists($rescue->image_path);
        $this->assertCount(4, $rescue->participants);
        $this->assertDatabaseHas('users', ['isa_id' => 'ISA_0000ABCD', 'cognito_sub' => null]);
        $this->assertDatabaseHas('users', ['email' => 'newbie@example.org', 'cognito_sub' => null]);
    }

    public function test_unknown_people_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())->post(route('rescues.store'), [
            'happened_on' => '2026-09-01',
            'location' => 'Lake Garda',
            'image' => UploadedFile::fake()->image('proof.jpg'),
            'participants' => [['person' => 'just a name', 'role' => 'victim']],
        ])->assertSessionHasErrors('participants.0.person');

        $this->assertDatabaseCount('rescues', 0);
    }

    #[DataProvider('rejectedPhotos')]
    public function test_rejects_photos_that_are_too_large_or_not_an_allowed_type(string $name, int $kilobytes, string $message): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('rescues.store'), [
            'happened_on' => '2026-09-01',
            'location' => 'Lake Garda',
            'image' => UploadedFile::fake()->image($name)->size($kilobytes),
            'participants' => [['person' => $user->isa_id, 'role' => 'victim']],
        ])->assertSessionHasErrors(['image' => $message]);

        $this->assertDatabaseCount('rescues', 0);
    }

    public static function rejectedPhotos(): array
    {
        return [
            'over 8 MB' => ['proof.jpg', 8 * 1024 + 1, 'The photo must be 8 MB or smaller.'],
            'gif' => ['proof.gif', 100, 'The photo must be a JPG, PNG or WebP image.'],
        ];
    }

    public function test_upload_over_the_server_post_limit_gets_a_friendly_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->call('POST', route('rescues.store'), server: ['CONTENT_LENGTH' => PHP_INT_MAX])
            ->assertStatus(413)
            ->assertSee('That file is too large');
    }

    public function test_only_involved_people_can_edit(): void
    {
        $involved = User::factory()->create();
        $rescue = Rescue::factory()->create();
        $rescue->syncParticipants([$involved->id => [RescueRole::Victim]]);

        $this->actingAs($involved)->get(route('rescues.edit', $rescue))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('rescues.edit', $rescue))->assertForbidden();
    }

    public function test_only_admins_can_delete(): void
    {
        $rescue = Rescue::factory()->create();

        $this->actingAs($rescue->creator)->delete(route('rescues.destroy', $rescue))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->delete(route('rescues.destroy', $rescue))->assertRedirect();

        $this->assertModelMissing($rescue);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\RescueRole;
use App\Models\Rescue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_pages_render_from_markdown(): void
    {
        $this->get('/')->assertOk()->assertSee('Practise rescues');
        $this->get('/rules')->assertOk()->assertSee('Ground rescuer');
        $this->get('/rescue_graphics')->assertOk();
        $this->get('/does-not-exist')->assertNotFound();
    }

    public function test_images_in_the_page_folder_are_shown_in_file_name_order(): void
    {
        $folder = public_path('images/rescue_graphics');
        $files = ['10-backup-system.jpg', '2-ground_rescue.PNG', '11.2 HIGHLINE OVERVIEW.png', '11.2 HIGHLINE OVERVIEW.pdf', 'notes.txt'];
        foreach ($files as $file) {
            file_put_contents("{$folder}/{$file}", 'x');
        }

        try {
            $this->get('/rescue_graphics')
                ->assertOk()
                ->assertSeeInOrder([
                    'images/rescue_graphics/2-ground_rescue.PNG', 'Ground rescue',
                    'images/rescue_graphics/10-backup-system.jpg', 'Backup system',
                    'images/rescue_graphics/11.2%20HIGHLINE%20OVERVIEW.png', 'Highline overview',
                    'images/rescue_graphics/11.2%20HIGHLINE%20OVERVIEW.pdf', 'download="11.2 HIGHLINE OVERVIEW.pdf"', 'Download PDF',
                ], escape: false)
                ->assertDontSee('notes.txt');
        } finally {
            foreach ($files as $file) {
                unlink("{$folder}/{$file}");
            }
        }
    }

    public function test_ranking_counts_rescues_per_role(): void
    {
        [$alex, $sam] = User::factory(2)->create();

        foreach (range(1, 2) as $_) {
            Rescue::factory()->create()->syncParticipants([
                $alex->id => [RescueRole::LineRescuer],
                $sam->id => [RescueRole::Victim],
            ]);
        }

        $ranking = User::ranking(RescueRole::LineRescuer)->get();

        $this->assertSame([$alex->id], $ranking->pluck('id')->all());
        $this->assertSame(2, $ranking->first()->rescue_count);
        $this->get('/ranking')->assertOk()->assertSeeInOrder([RescueRole::Victim->label(), $sam->name]);
    }

    public function test_profile_lists_the_persons_rescues(): void
    {
        $user = User::factory()->create();
        $rescue = Rescue::factory()->create(['location' => 'Monte Piana']);
        $rescue->syncParticipants([$user->id => [RescueRole::GroundRescuer]]);

        $this->get(route('people.show', $user))
            ->assertOk()
            ->assertSee('Monte Piana')
            ->assertSee(RescueRole::GroundRescuer->label());
    }
}

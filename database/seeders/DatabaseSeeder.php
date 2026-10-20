<?php

namespace Database\Seeders;

use App\Enums\RescueRole;
use App\Models\Rescue;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Demo data for local development.
     */
    public function run(): void
    {
        // The two accounts offered on the local login page (no ISA account needed).
        $member = User::factory()->create(['name' => 'Test Member', 'email' => 'member@example.org']);
        User::factory()->admin()->create(['name' => 'Test Admin', 'email' => 'admin@example.org']);

        $users = User::factory(12)->create()->push($member);

        Rescue::factory(20)
            ->recycle($users)
            ->create()
            ->each(function (Rescue $rescue) use ($users) {
                // One file per rescue: deleting a rescue deletes its photo.
                $rescue->update(['image_path' => Storage::disk('public')->putFile('rescues', UploadedFile::fake()->image('example.jpg', 1200, 900))]);

                [$victim, $line, $ground] = $users->random(3)->all();
                $rescue->syncParticipants([
                    $victim->id => [RescueRole::Victim],
                    $line->id => [RescueRole::LineRescuer],
                    $ground->id => [RescueRole::GroundRescuer],
                ]);
            });
    }
}

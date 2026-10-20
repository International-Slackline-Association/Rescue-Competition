<?php

namespace Tests\Feature;

use App\Filament\Resources\Rescues\Pages\ListRescues;
use App\Models\Rescue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_is_admin_only(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk();
    }

    public function test_admin_sees_rescues(): void
    {
        $rescues = Rescue::factory(3)->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListRescues::class)->assertCanSeeTableRecords($rescues);
    }
}

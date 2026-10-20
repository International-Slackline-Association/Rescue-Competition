<?php

namespace Tests\Feature;

use App\Enums\RescueRole;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Mcp\Servers\RescueServer;
use App\Mcp\Tools\DeleteRescueTool;
use App\Mcp\Tools\ListRescuesTool;
use App\Mcp\Tools\MergeUsersTool;
use App\Mcp\Tools\RankingTool;
use App\Models\Rescue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class McpTest extends TestCase
{
    use RefreshDatabase;

    private const LIST_TOOLS = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'];

    public function test_requests_without_a_token_are_rejected(): void
    {
        $this->postJson('/mcp', self::LIST_TOOLS)->assertUnauthorized();
    }

    public function test_tokens_of_non_admins_are_rejected(): void
    {
        $token = User::factory()->create()->createToken('mcp')->plainTextToken;

        $this->withToken($token)->postJson('/mcp', self::LIST_TOOLS)->assertForbidden();
    }

    public function test_admin_token_lists_the_tools(): void
    {
        $token = User::factory()->admin()->create()->createToken('mcp')->plainTextToken;

        $response = $this->withToken($token)->postJson('/mcp', self::LIST_TOOLS)->assertOk();

        $this->assertEqualsCanonicalizing(
            ['list_rescues', 'ranking', 'delete_rescue', 'merge_users'],
            array_column($response->json('result.tools'), 'name'),
        );
    }

    public function test_list_rescues_filters_by_user(): void
    {
        $person = User::factory()->create();
        $theirs = Rescue::factory()->create(['location' => 'Lake Garda']);
        $theirs->syncParticipants([$person->id => [RescueRole::Victim]]);
        Rescue::factory()->create(['location' => 'Somewhere else']);

        RescueServer::actingAs(User::factory()->admin()->create())
            ->tool(ListRescuesTool::class, ['user_id' => $person->id])
            ->assertOk()
            ->assertSee(['Lake Garda', $person->isa_id, 'victim'])
            ->assertDontSee('Somewhere else');
    }

    public function test_ranking_counts_only_the_given_role(): void
    {
        $often = User::factory()->create(['name' => 'Often Rescuer']);
        $once = User::factory()->create(['name' => 'Once Rescuer']);
        $victim = User::factory()->create(['name' => 'Only Victim']);

        Rescue::factory(2)->create()->each(fn (Rescue $rescue) => $rescue->syncParticipants([
            $often->id => [RescueRole::LineRescuer],
            $victim->id => [RescueRole::Victim],
        ]));
        Rescue::factory()->create()->syncParticipants([$once->id => [RescueRole::LineRescuer]]);

        RescueServer::actingAs(User::factory()->admin()->create())
            ->tool(RankingTool::class, ['role' => 'line_rescuer'])
            ->assertOk()
            ->assertSee(['"rank":1,"id":'.$often->id, '"rescue_count":2', '"rank":2,"id":'.$once->id])
            ->assertDontSee('Only Victim');
    }

    public function test_ranking_rejects_unknown_roles(): void
    {
        RescueServer::actingAs(User::factory()->admin()->create())
            ->tool(RankingTool::class, ['role' => 'hero'])
            ->assertHasErrors();
    }

    public function test_admin_deletes_a_rescue_and_its_photo(): void
    {
        Storage::fake('public');
        $rescue = Rescue::factory()->create(['image_path' => 'rescues/proof.jpg']);
        Storage::disk('public')->put('rescues/proof.jpg', 'image');

        RescueServer::actingAs(User::factory()->admin()->create())
            ->tool(DeleteRescueTool::class, ['rescue_id' => $rescue->id])
            ->assertOk();

        $this->assertModelMissing($rescue);
        Storage::disk('public')->assertMissing('rescues/proof.jpg');
    }

    public function test_delete_goes_through_the_rescue_policy(): void
    {
        $creator = User::factory()->create();
        $rescue = Rescue::factory()->for($creator, 'creator')->create();

        RescueServer::actingAs($creator)
            ->tool(DeleteRescueTool::class, ['rescue_id' => $rescue->id])
            ->assertHasErrors(['Permission denied.']);

        $this->assertModelExists($rescue);
    }

    public function test_merge_moves_rescues_and_deletes_the_placeholder(): void
    {
        $placeholder = User::factory()->placeholder()->create();
        $real = User::factory()->create();
        $shared = Rescue::factory()->create();
        $shared->syncParticipants([$placeholder->id => [RescueRole::Victim], $real->id => [RescueRole::Victim]]);
        $submitted = Rescue::factory()->for($placeholder, 'creator')->create();
        $submitted->syncParticipants([$placeholder->id => [RescueRole::GroundRescuer]]);

        RescueServer::actingAs(User::factory()->admin()->create())
            ->tool(MergeUsersTool::class, ['placeholder_id' => $placeholder->id, 'into_user_id' => $real->id])
            ->assertOk();

        $this->assertModelMissing($placeholder);
        $this->assertSame($real->id, $submitted->fresh()->created_by);
        $this->assertDatabaseHas('rescue_user', ['rescue_id' => $submitted->id, 'user_id' => $real->id, 'role' => 'ground_rescuer']);
        $this->assertDatabaseCount('rescue_user', 2);
    }

    public function test_merge_refuses_users_who_have_logged_in(): void
    {
        $real = User::factory()->create();

        RescueServer::actingAs(User::factory()->admin()->create())
            ->tool(MergeUsersTool::class, ['placeholder_id' => $real->id, 'into_user_id' => User::factory()->create()->id])
            ->assertHasErrors();

        $this->assertModelExists($real);
    }

    public function test_merge_is_admin_only(): void
    {
        $placeholder = User::factory()->placeholder()->create();
        $user = User::factory()->create();

        RescueServer::actingAs($user)
            ->tool(MergeUsersTool::class, ['placeholder_id' => $placeholder->id, 'into_user_id' => $user->id])
            ->assertHasErrors(['Permission denied.']);

        $this->assertModelExists($placeholder);
    }

    public function test_admin_issues_a_new_token_for_themselves_only(): void
    {
        $admin = User::factory()->admin()->create();
        $old = $admin->createToken('mcp')->accessToken;
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $admin->getKey()])
            ->callAction('mcpToken')
            ->assertNotified('Your MCP token');

        $this->assertModelMissing($old);
        $this->assertSame(1, $admin->tokens()->count());

        Livewire::test(EditUser::class, ['record' => User::factory()->admin()->create()->getKey()])
            ->assertActionHidden('mcpToken');
    }
}

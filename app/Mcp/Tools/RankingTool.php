<?php

namespace App\Mcp\Tools;

use App\Enums\RescueRole;
use App\Models\Rescue;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('ranking')]
#[Description('Ranks users by how many rescues they took part in with the given role, same as the public /ranking page.')]
#[IsReadOnly]
class RankingTool extends Tool
{
    public function handle(Request $request): Response
    {
        if ($request->user()->cannot('viewAny', Rescue::class)) {
            return Response::error('Permission denied.');
        }

        $validated = $request->validate([
            'role' => ['required', Rule::enum(RescueRole::class)],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $ranking = User::ranking(RescueRole::from($validated['role']))
            ->limit($validated['limit'] ?? 50)
            ->get();

        return Response::json($ranking->values()->map(fn (User $user, int $index) => [
            'rank' => $index + 1,
            ...ListRescuesTool::person($user),
            'rescue_count' => $user->rescue_count,
        ]));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'role' => $schema->string()
                ->enum(array_column(RescueRole::cases(), 'value'))
                ->description('The role to rank by.')
                ->required(),
            'limit' => $schema->integer()
                ->description('Maximum number of users (1-200).')
                ->default(50),
        ];
    }
}

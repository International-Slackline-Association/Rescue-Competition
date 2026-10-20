<?php

namespace App\Mcp\Tools;

use App\Models\Rescue;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_rescues')]
#[Description('Lists rescues, newest first, with their creator and participants. Optionally only the rescues of one user.')]
#[IsReadOnly]
class ListRescuesTool extends Tool
{
    public function handle(Request $request): Response
    {
        if ($request->user()->cannot('viewAny', Rescue::class)) {
            return Response::error('Permission denied.');
        }

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $rescues = Rescue::with('creator', 'participants')
            ->when($validated['user_id'] ?? null, fn ($query, int $userId) => $query->where(fn ($query) => $query
                ->whereHas('participants', fn ($participants) => $participants->whereKey($userId))
                ->orWhere('created_by', $userId)))
            ->latest('happened_on')
            ->latest('id')
            ->limit($validated['limit'] ?? 50)
            ->get();

        return Response::json($rescues->map(fn (Rescue $rescue) => [
            'id' => $rescue->id,
            'happened_on' => $rescue->happened_on->toDateString(),
            'location' => $rescue->location,
            'description' => $rescue->description,
            'image_url' => $rescue->imageUrl(),
            'created_by' => self::person($rescue->creator),
            'participants' => $rescue->participants->map(fn (User $user) => [
                ...self::person($user),
                'role' => $user->pivot->role,
            ]),
        ]));
    }

    /**
     * @return array{id: int, isa_id: ?string, name: string, email: ?string, placeholder: bool}
     */
    public static function person(User $user): array
    {
        return [
            'id' => $user->id,
            'isa_id' => $user->isa_id,
            'name' => $user->name,
            'email' => $user->email,
            'placeholder' => $user->cognito_sub === null,
        ];
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()
                ->description('Only rescues this user took part in or submitted.'),
            'limit' => $schema->integer()
                ->description('Maximum number of rescues (1-200).')
                ->default(50),
        ];
    }
}

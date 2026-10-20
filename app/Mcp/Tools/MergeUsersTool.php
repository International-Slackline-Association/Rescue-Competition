<?php

namespace App\Mcp\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('merge_users')]
#[Description('Merges a placeholder user (never logged in) into another user: their rescue roles and submitted rescues move over, then the placeholder is deleted.')]
#[IsDestructive]
class MergeUsersTool extends Tool
{
    public function handle(Request $request): Response
    {
        if ($request->user()->cannot('admin')) {
            return Response::error('Permission denied.');
        }

        $validated = $request->validate([
            'placeholder_id' => ['required', 'integer', 'exists:users,id'],
            'into_user_id' => ['required', 'integer', 'exists:users,id', 'different:placeholder_id'],
        ]);

        $placeholder = User::findOrFail($validated['placeholder_id']);
        $target = User::findOrFail($validated['into_user_id']);

        if ($placeholder->cognito_sub !== null) {
            return Response::error("User {$placeholder->id} has logged in before, so it is not a placeholder and cannot be merged away.");
        }

        $placeholder->mergeInto($target);

        return Response::text("Merged {$placeholder->name} (#{$placeholder->id}) into {$target->name} (#{$target->id}).");
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'placeholder_id' => $schema->integer()
                ->description('The placeholder user to merge away (placeholder: true in list_rescues).')
                ->required(),
            'into_user_id' => $schema->integer()
                ->description('The user who keeps the rescues.')
                ->required(),
        ];
    }
}

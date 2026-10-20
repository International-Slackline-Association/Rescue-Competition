<?php

namespace App\Mcp\Tools;

use App\Models\Rescue;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('delete_rescue')]
#[Description('Permanently deletes a rescue, its participant list and its proof photo.')]
#[IsDestructive]
class DeleteRescueTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate(['rescue_id' => ['required', 'integer']]);

        $rescue = Rescue::find($validated['rescue_id']);

        if (! $rescue) {
            return Response::error("Rescue {$validated['rescue_id']} does not exist.");
        }

        if ($request->user()->cannot('delete', $rescue)) {
            return Response::error('Permission denied.');
        }

        $rescue->delete();

        return Response::text("Deleted rescue {$rescue->id} ({$rescue->location}, {$rescue->happened_on->toDateString()}).");
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'rescue_id' => $schema->integer()
                ->description('The id from list_rescues.')
                ->required(),
        ];
    }
}

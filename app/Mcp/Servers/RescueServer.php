<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\DeleteRescueTool;
use App\Mcp\Tools\ListRescuesTool;
use App\Mcp\Tools\MergeUsersTool;
use App\Mcp\Tools\RankingTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Slack Rescue Competition')]
#[Version('1.0.0')]
#[Instructions('Admin tools for the Slack Rescue Competition, a rescue practice competition. Rescues have participants with a role (victim, line_rescuer, ground_rescuer). Users without a login (placeholder: true) were added to a rescue by someone else; merge them into the real account once it exists.')]
class RescueServer extends Server
{
    protected array $tools = [
        ListRescuesTool::class,
        RankingTool::class,
        DeleteRescueTool::class,
        MergeUsersTool::class,
    ];
}

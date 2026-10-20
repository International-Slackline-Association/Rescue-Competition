<?php

use App\Mcp\Servers\RescueServer;
use Laravel\Mcp\Facades\Mcp;

// Admins only: a Sanctum token from the admin panel (Users → your account → MCP token).
Mcp::web('/mcp', RescueServer::class)
    ->middleware(['auth:sanctum', 'can:admin']);

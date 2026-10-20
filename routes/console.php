<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

// Bootstrap the first admin on a fresh server; after that, admins manage each other in /admin.
Artisan::command('admin:grant {person : Email or ISA ID of someone who has logged in once}', function (string $person) {
    $user = User::where('email', strtolower($person))->orWhere('isa_id', strtoupper($person))->first();

    if (! $user) {
        $this->error("No user with email or ISA ID {$person}. They need to log in once first.");

        return 1;
    }

    $user->forceFill(['is_admin' => true])->save();
    $this->info("{$user->name} is now an admin.");
})->purpose('Make a user an admin');

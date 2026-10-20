<x-layout title="Log in">
    <div class="mx-auto max-w-md">
        <h1 class="heading text-3xl">Log in (local)</h1>
        <p class="mt-2 text-sm text-muted">
            No ISA login is configured (<code>COGNITO_CLIENT_ID</code> is empty), so pick a seeded account.
        </p>

        <ul class="mt-8 divide-y divide-line rounded-lg border border-line bg-white">
            @forelse ($users as $user)
                <li>
                    <form method="post" action="{{ route('dev.login', $user) }}">
                        @csrf
                        <button class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-paper">
                            <x-avatar :user="$user" />
                            <span class="mr-auto">{{ $user->name }}</span>
                            @if ($user->is_admin)
                                <span class="rounded-full bg-label/10 px-2 py-0.5 text-xs font-semibold text-label">Admin</span>
                            @endif
                        </button>
                    </form>
                </li>
            @empty
                <li class="px-4 py-3 text-sm text-muted">No users yet. Run <code>sail artisan migrate --seed</code>.</li>
            @endforelse
        </ul>
    </div>
</x-layout>

@props(['role', 'people'])
<section class="frame p-5">
    <h2 class="heading flex items-center gap-2"><x-role-chip :role="$role" />{{ $role->label() }}</h2>
    <ol class="mt-4 space-y-3">
        @forelse ($people as $i => $person)
            <li class="flex items-center gap-3">
                <span class="w-5 text-right text-sm tabular-nums text-muted">{{ $i + 1 }}</span>
                <x-avatar :user="$person" />
                <a href="{{ route('people.show', $person) }}" class="flex-1 truncate hover:text-label">{{ $person->name }}</a>
                <span class="font-semibold tabular-nums">{{ $person->rescue_count }}</span>
            </li>
        @empty
            <li class="text-sm text-muted">No rescues yet.</li>
        @endforelse
    </ol>
</section>

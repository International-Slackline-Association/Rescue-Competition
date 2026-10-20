<x-layout :title="$rescue->location">
    <div class="grid gap-8 md:grid-cols-[3fr_2fr]">
        <img src="{{ $rescue->imageUrl() }}" alt="Proof photo of the rescue" class="w-full rounded-xl border border-line object-cover">

        <div>
            <p class="text-sm uppercase tracking-wide text-muted">{{ $rescue->happened_on->toFormattedDateString() }}</p>
            <h1 class="heading mt-1 text-3xl">{{ $rescue->location }}</h1>

            @if ($rescue->description)
                <p class="mt-4 whitespace-pre-line">{{ $rescue->description }}</p>
            @endif

            <h2 class="mt-8 text-sm font-semibold uppercase tracking-wide text-muted">Involved</h2>
            <ul class="mt-3 space-y-3">
                @foreach ($rescue->participants as $person)
                    <li class="flex items-center gap-3">
                        <x-avatar :user="$person" />
                        <a href="{{ route('people.show', $person) }}" class="hover:text-label">{{ $person->name }}</a>
                        <span class="ml-auto flex items-center gap-1.5 text-xs text-label"><x-role-chip :role="\App\Enums\RescueRole::from($person->pivot->role)" />{{ \App\Enums\RescueRole::from($person->pivot->role)->label() }}</span>
                    </li>
                @endforeach
            </ul>

            <p class="mt-8 text-xs text-muted">Submitted by {{ $rescue->creator->name }}</p>

            <div class="mt-6 flex gap-3">
                @can('update', $rescue)
                    <a href="{{ route('rescues.edit', $rescue) }}" class="btn-ghost">Edit</a>
                @endcan
                @can('delete', $rescue)
                    <form method="post" action="{{ route('rescues.destroy', $rescue) }}" onsubmit="return confirm('Delete this rescue for everyone?')">
                        @csrf @method('delete')
                        <button class="btn-ghost text-edge">Delete</button>
                    </form>
                @endcan
            </div>
        </div>
    </div>
</x-layout>

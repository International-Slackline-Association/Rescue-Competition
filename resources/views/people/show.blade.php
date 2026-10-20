<x-layout :title="$person->name">
    <header class="flex items-center gap-5">
        <x-avatar :user="$person" size="size-20" class="text-2xl" />
        <div>
            <h1 class="heading text-3xl">{{ $person->name }}</h1>
            <p class="mt-1 text-sm text-muted">
                {{ $person->isa_id ?? 'No ISA account linked yet' }} · {{ trans_choice(':count rescue|:count rescues', $rescues->count()) }}
            </p>
        </div>
    </header>

    <section class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($rescues as $rescue)
            <div>
                <x-rescue-card :rescue="$rescue" />
                <p class="mt-2 px-1 text-sm text-muted">
                    {{ $rescue->participants->where('id', $person->id)->map(fn ($p) => \App\Enums\RescueRole::from($p->pivot->role)->label())->join(', ') }}
                </p>
            </div>
        @empty
            <p class="text-muted">No rescues yet.</p>
        @endforelse
    </section>
</x-layout>

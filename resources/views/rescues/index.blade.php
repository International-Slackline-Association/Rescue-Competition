<x-layout title="Rescues">
    <h1 class="heading text-3xl">Rescues</h1>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($rescues as $rescue)
            <x-rescue-card :rescue="$rescue" />
        @empty
            <p class="text-muted">No rescues submitted yet.</p>
        @endforelse
    </div>

    <div class="mt-8">{{ $rescues->links() }}</div>
</x-layout>

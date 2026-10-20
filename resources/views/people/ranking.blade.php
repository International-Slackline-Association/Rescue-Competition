<x-layout title="Ranking">
    <h1 class="heading text-3xl">Ranking</h1>
    <p class="mt-2 text-muted">Number of rescues per person and role.</p>

    <div class="mt-8 grid gap-4 md:grid-cols-3">
        @foreach (\App\Enums\RescueRole::cases() as $role)
            <x-ranking-list :role="$role" :people="$rankings[$role->value]" />
        @endforeach
    </div>
</x-layout>

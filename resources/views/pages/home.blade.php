<x-layout>
    <section class="grid gap-10 md:grid-cols-[3fr_2fr] md:items-center">
        <div class="prose prose-lg max-w-none prose-headings:tracking-tight prose-headings:text-label prose-headings:font-semibold">
            {!! $page['html'] !!}
        </div>
        <div class="flex flex-col items-start gap-3">
            @auth
                <a href="{{ route('rescues.create') }}" class="btn">Submit a rescue</a>
            @else
                <a href="{{ route('login') }}" class="btn">Log in with ISA account</a>
            @endauth
            <a href="{{ route('page', 'rules') }}" class="btn-ghost">Read the rules</a>
        </div>
    </section>

    <section class="mt-16">
        <div class="flex items-baseline justify-between">
            <h2 class="heading text-xl">Leaderboard</h2>
            <a href="{{ route('ranking') }}" class="text-sm text-muted hover:text-ink">Full ranking →</a>
        </div>
        <div class="mt-4 grid gap-4 md:grid-cols-3">
            @foreach (\App\Enums\RescueRole::cases() as $role)
                <x-ranking-list :role="$role" :people="$leaders[$role->value]" />
            @endforeach
        </div>
    </section>

    @if ($latest->isNotEmpty())
        <section class="mt-16">
            <div class="flex items-baseline justify-between">
                <h2 class="heading text-xl">Latest rescues</h2>
                <a href="{{ route('rescues.index') }}" class="text-sm text-muted hover:text-ink">All rescues →</a>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($latest as $rescue)
                    <x-rescue-card :rescue="$rescue" />
                @endforeach
            </div>
        </section>
    @endif
</x-layout>

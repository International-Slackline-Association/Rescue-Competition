@props(['rescue'])
<a href="{{ route('rescues.show', $rescue) }}" class="card group block">
    <img src="{{ $rescue->imageUrl() }}" alt="" class="aspect-[4/3] w-full object-cover transition group-hover:opacity-90" loading="lazy">
    <div class="p-4">
        <p class="text-xs uppercase tracking-wide text-muted">{{ $rescue->happened_on->toFormattedDateString() }}</p>
        <p class="mt-1 font-semibold">{{ $rescue->location }}</p>
        <div class="mt-3 flex -space-x-2">
            @foreach ($rescue->participants->unique('id') as $person)
                <x-avatar :user="$person" class="ring-2 ring-white" />
            @endforeach
        </div>
    </div>
</a>

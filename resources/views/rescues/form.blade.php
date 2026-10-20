@php
    $participants = old('participants', $rescue->exists
        ? $rescue->participants->map(fn ($p) => ['person' => $p->isa_id ?? $p->email, 'role' => $p->pivot->role])->all()
        : [['person' => auth()->user()->isa_id ?? auth()->user()->email, 'role' => '']]);
@endphp

<x-layout :title="$rescue->exists ? 'Edit rescue' : 'Submit a rescue'">
    <h1 class="heading text-3xl">{{ $rescue->exists ? 'Edit rescue' : 'Submit a rescue' }}</h1>

    <form method="post" enctype="multipart/form-data"
          action="{{ $rescue->exists ? route('rescues.update', $rescue) : route('rescues.store') }}"
          class="mt-8 grid max-w-2xl gap-6">
        @csrf
        @if ($rescue->exists) @method('put') @endif

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="happened_on" class="label">Date</label>
                <input id="happened_on" name="happened_on" type="date" required class="field" max="{{ now()->toDateString() }}"
                       value="{{ old('happened_on', $rescue->happened_on?->toDateString()) }}">
                @error('happened_on') <p class="mt-1 text-sm text-edge">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="location" class="label">Location</label>
                <input id="location" name="location" required class="field" placeholder="Spot, city, country"
                       value="{{ old('location', $rescue->location) }}">
                @error('location') <p class="mt-1 text-sm text-edge">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="image" class="label">Proof photo</label>
            @if ($rescue->exists)
                <img src="{{ $rescue->imageUrl() }}" alt="" class="mb-2 h-32 rounded-lg object-cover">
            @endif
            <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" @required(! $rescue->exists)
                   data-max-bytes="{{ \App\Http\Requests\RescueRequest::MAX_IMAGE_MB * 1024 * 1024 }}"
                   data-too-large="The photo must be {{ \App\Http\Requests\RescueRequest::MAX_IMAGE_MB }} MB or smaller."
                   class="block w-full text-sm file:mr-4 file:rounded-full file:border-0 file:bg-ink/5 file:px-4 file:py-2 file:font-medium">
            <p class="mt-1 text-sm text-muted">JPG, PNG or WebP, up to {{ \App\Http\Requests\RescueRequest::MAX_IMAGE_MB }} MB.</p>
            @error('image') <p class="mt-1 text-sm text-edge">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="description" class="label">What happened? <span class="font-normal text-muted">(optional)</span></label>
            <textarea id="description" name="description" rows="4" class="field">{{ old('description', $rescue->description) }}</textarea>
        </div>

        <fieldset>
            <legend class="label">Involved people</legend>
            <p class="mb-3 text-sm text-muted">Search by name, or enter the ISA ID or email of someone who hasn't logged in yet.</p>
            <div data-participants class="space-y-2">
                @foreach ($participants as $i => $row)
                    @include('rescues.participant-row', ['i' => $i, 'row' => $row])
                @endforeach
            </div>
            @error('participants') <p class="mt-1 text-sm text-edge">{{ $message }}</p> @enderror
            <button type="button" data-add-participant class="btn-ghost mt-3">+ Add person</button>
        </fieldset>

        <datalist id="people"></datalist>
        <template id="participant-row">
            @include('rescues.participant-row', ['i' => '__i__', 'row' => ['person' => '', 'role' => '']])
        </template>

        <div>
            <button class="btn">{{ $rescue->exists ? 'Save changes' : 'Submit rescue' }}</button>
        </div>
    </form>
</x-layout>

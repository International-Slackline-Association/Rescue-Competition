<div data-participant>
    <div class="flex gap-2">
        <input name="participants[{{ $i }}][person]" value="{{ $row['person'] }}" list="people" required autocomplete="off"
               data-people-search="{{ route('people.search') }}" placeholder="Name, ISA ID or email" class="field flex-1">
        <select name="participants[{{ $i }}][role]" required class="field w-52">
            <option value="">Role…</option>
            @foreach (\App\Enums\RescueRole::cases() as $role)
                <option value="{{ $role->value }}" @selected($row['role'] === $role->value)>{{ $role->label() }}</option>
            @endforeach
        </select>
        <button type="button" data-remove-participant class="px-2 text-muted hover:text-edge" aria-label="Remove">✕</button>
    </div>
    @error("participants.$i.person") <p class="mt-1 text-sm text-edge">{{ $message }}</p> @enderror
</div>

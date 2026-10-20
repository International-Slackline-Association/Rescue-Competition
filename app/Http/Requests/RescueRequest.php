<?php

namespace App\Http\Requests;

use App\Enums\RescueRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class RescueRequest extends FormRequest
{
    /**
     * Proof photos: plenty for a phone picture, small enough for shared hosting storage.
     * Keep public/.user.ini (upload_max_filesize) a little above this.
     */
    public const MAX_IMAGE_MB = 8;

    public const IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'webp'];

    public function rules(): array
    {
        return [
            'happened_on' => ['required', 'date', 'before_or_equal:today'],
            'location' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => [
                $this->route('rescue') ? 'nullable' : 'required',
                File::image()->types(self::IMAGE_TYPES)->max(self::MAX_IMAGE_MB * 1024),
            ],
            'participants' => ['required', 'array', 'min:1'],
            'participants.*.person' => ['required', 'string', 'max:255'],
            'participants.*.role' => ['required', Rule::enum(RescueRole::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'image.max' => 'The photo must be '.self::MAX_IMAGE_MB.' MB or smaller.',
            'image.mimes' => 'The photo must be a JPG, PNG or WebP image.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            foreach ($this->input('participants', []) as $i => $row) {
                if (! $this->isIdentifier($row['person'] ?? '')) {
                    $validator->errors()->add("participants.$i.person", 'Pick a person from the list, or enter an ISA ID (ISA_XXXXXXXX) or email.');
                }
            }
        }];
    }

    public function rescueData(): array
    {
        return $this->safe()->only(['happened_on', 'location', 'description']);
    }

    /**
     * Resolve each row to a user, creating placeholders for people who haven't logged in yet.
     *
     * @return array<int, list<RescueRole>>
     */
    public function participants(): array
    {
        $roles = [];

        foreach ($this->validated('participants') as $row) {
            $user = $this->resolve(trim($row['person']));
            $role = RescueRole::from($row['role']);

            if (! in_array($role, $roles[$user->id] ?? [], true)) {
                $roles[$user->id][] = $role;
            }
        }

        return $roles;
    }

    private function resolve(string $person): User
    {
        if (Str::startsWith(strtoupper($person), 'ISA_')) {
            $isaId = strtoupper($person);

            return User::firstOrCreate(['isa_id' => $isaId], ['name' => $isaId]);
        }

        return User::firstOrCreate(['email' => strtolower($person)], ['name' => Str::before($person, '@')]);
    }

    private function isIdentifier(string $person): bool
    {
        $person = trim($person);

        return preg_match('/^ISA_[0-9A-F]{8}$/i', $person) || filter_var($person, FILTER_VALIDATE_EMAIL);
    }
}

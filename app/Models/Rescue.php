<?php

namespace App\Models;

use App\Enums\RescueRole;
use Database\Factories\RescueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['happened_on', 'location', 'description', 'image_path'])]
class Rescue extends Model
{
    /** @use HasFactory<RescueFactory> */
    use HasFactory;

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role');
    }

    public function involves(User $user): bool
    {
        return $this->participants()->whereKey($user->id)->exists();
    }

    /**
     * Replace all participants with the given [user_id => [role, ...]] map.
     *
     * @param  array<int, list<RescueRole>>  $rolesByUser
     */
    public function syncParticipants(array $rolesByUser): void
    {
        $this->participants()->detach();

        foreach ($rolesByUser as $userId => $roles) {
            foreach ($roles as $role) {
                $this->participants()->attach($userId, ['role' => $role]);
            }
        }
    }

    public function imageUrl(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }

    protected static function booted(): void
    {
        static::deleted(fn (Rescue $rescue) => Storage::disk('public')->delete($rescue->image_path));
    }

    protected function casts(): array
    {
        return [
            'happened_on' => 'date',
        ];
    }
}

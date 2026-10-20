<?php

namespace App\Models;

use App\Enums\RescueRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['isa_id', 'cognito_sub', 'name', 'email', 'avatar_url'])]
#[Hidden(['remember_token', 'cognito_sub'])]
class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    protected $attributes = [
        'is_admin' => false,
    ];

    /**
     * Same derivation as isa-users: ISA_ + first 8 hex chars of sha256(cognito username).
     */
    public static function isaIdFromUsername(string $username): string
    {
        return 'ISA_'.strtoupper(substr(hash('sha256', $username), 0, 8));
    }

    public function rescues(): BelongsToMany
    {
        return $this->belongsToMany(Rescue::class)->withPivot('role');
    }

    /**
     * Users ranked by how many rescues they took part in with the given role.
     */
    public function scopeRanking(Builder $query, RescueRole $role): void
    {
        $byRole = fn (Builder $q) => $q->where('rescue_user.role', $role);

        $query->whereHas('rescues', $byRole)
            ->withCount(['rescues as rescue_count' => $byRole])
            ->orderByDesc('rescue_count')
            ->orderBy('name');
    }

    /**
     * Move this (placeholder) user's rescue roles and submitted rescues to $target, then delete it.
     */
    public function mergeInto(User $target): void
    {
        DB::transaction(function () use ($target) {
            // insertOrIgnore: $target may already hold the same role in the same rescue.
            DB::table('rescue_user')->insertOrIgnore(
                DB::table('rescue_user')->where('user_id', $this->id)->get(['rescue_id', 'role'])
                    ->map(fn ($row) => ['rescue_id' => $row->rescue_id, 'user_id' => $target->id, 'role' => $row->role])
                    ->all()
            );

            Rescue::where('created_by', $this->id)->update(['created_by' => $target->id]);

            $this->delete();
        });
    }

    /**
     * Users log in through ISA only, so there is no password column.
     */
    public function getAuthPassword(): string
    {
        return '';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\RescueRole;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PersonController extends Controller
{
    public function ranking(): View
    {
        return view('people.ranking', [
            'rankings' => collect(RescueRole::cases())
                ->mapWithKeys(fn (RescueRole $role) => [$role->value => User::ranking($role)->limit(50)->get()]),
        ]);
    }

    public function show(User $user): View
    {
        return view('people.show', [
            'person' => $user,
            'rescues' => $user->rescues()->with('participants')->latest('happened_on')->get()->unique('id'),
        ]);
    }

    /**
     * People picker for the rescue form: only accounts known to this site are searchable.
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json(
            // Name search only finds ISA accounts; email-only placeholders need their exact email, so their addresses don't leak.
            User::where(fn ($query) => $query->whereNotNull('isa_id')->where('name', 'like', "%{$q}%"))
                ->orWhere('isa_id', strtoupper($q))
                ->orWhere('email', strtolower($q))
                ->orderBy('name')
                ->limit(10)
                ->get()
                ->map(fn (User $u) => ['value' => $u->isa_id ?? $u->email, 'label' => $u->name])
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\RescueRequest;
use App\Models\Rescue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RescueController extends Controller
{
    public function index(): View
    {
        return view('rescues.index', [
            'rescues' => Rescue::with('participants')->latest('happened_on')->paginate(24),
        ]);
    }

    public function show(Rescue $rescue): View
    {
        return view('rescues.show', ['rescue' => $rescue->load('participants', 'creator')]);
    }

    public function create(): View
    {
        return view('rescues.form', ['rescue' => new Rescue]);
    }

    public function store(RescueRequest $request): RedirectResponse
    {
        $rescue = DB::transaction(function () use ($request) {
            $rescue = new Rescue($request->rescueData());
            $rescue->created_by = $request->user()->id;
            $rescue->image_path = $request->file('image')->store('rescues', 'public');
            $rescue->save();
            $rescue->syncParticipants($request->participants());

            return $rescue;
        });

        return redirect()->route('rescues.show', $rescue)->with('status', 'Rescue submitted.');
    }

    public function edit(Rescue $rescue): View
    {
        Gate::authorize('update', $rescue);

        return view('rescues.form', ['rescue' => $rescue->load('participants')]);
    }

    public function update(RescueRequest $request, Rescue $rescue): RedirectResponse
    {
        Gate::authorize('update', $rescue);

        DB::transaction(function () use ($request, $rescue) {
            $rescue->fill($request->rescueData());

            if ($request->hasFile('image')) {
                $old = $rescue->image_path;
                $rescue->image_path = $request->file('image')->store('rescues', 'public');
                DB::afterCommit(fn () => Storage::disk('public')->delete($old));
            }

            $rescue->save();
            $rescue->syncParticipants($request->participants());
        });

        return redirect()->route('rescues.show', $rescue)->with('status', 'Rescue updated.');
    }

    public function destroy(Rescue $rescue): RedirectResponse
    {
        Gate::authorize('delete', $rescue);

        $rescue->delete();

        return redirect()->route('rescues.index')->with('status', 'Rescue deleted.');
    }
}

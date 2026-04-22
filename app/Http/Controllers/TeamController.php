<?php

namespace App\Http\Controllers;

use App\Http\Requests\Team\StoreTeamMemberRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TeamController extends Controller
{
    /**
     * Medewerkersoverzicht (placeholder voor US-03 — volledige tabel komt in US-04).
     */
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $members = User::where('team_id', auth()->user()->team_id)
            ->orderBy('name')
            ->get();

        return view('team.index', compact('members'));
    }

    /**
     * Formulier voor nieuwe medewerker.
     */
    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('team.create');
    }

    /**
     * Opslaan van nieuwe medewerker (US-03 kern).
     */
    public function store(StoreTeamMemberRequest $request): RedirectResponse
    {
        User::create($request->validatedPayload());

        return redirect()
            ->route('team.index')
            ->with('success', 'Medewerker aangemaakt.');
    }
}

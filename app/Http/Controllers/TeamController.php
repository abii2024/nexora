<?php

namespace App\Http\Controllers;

use App\Http\Requests\Team\StoreTeamMemberRequest;
use App\Http\Requests\Team\UpdateTeamMemberRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    /**
     * Medewerkersoverzicht met zoek- en filtermogelijkheden (US-04).
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->query('search', ''));
        $role = $request->query('role');
        $status = $request->query('status');

        $query = User::query()
            ->where('team_id', auth()->user()->team_id);

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (in_array($role, [User::ROLE_ZORGBEGELEIDER, User::ROLE_TEAMLEIDER], true)) {
            $query->where('role', $role);
        }

        if ($status === 'actief') {
            $query->where('is_active', true);
        } elseif ($status === 'inactief') {
            $query->where('is_active', false);
        }

        // Actieven bovenaan, daarna alfabetisch op naam.
        $query->orderByDesc('is_active')->orderBy('name');

        $members = $query->paginate(25)->withQueryString();

        $totals = [
            'actief' => User::where('team_id', auth()->user()->team_id)->where('is_active', true)->count(),
            'inactief' => User::where('team_id', auth()->user()->team_id)->where('is_active', false)->count(),
        ];

        return view('team.index', [
            'members' => $members,
            'totals' => $totals,
            'filters' => [
                'search' => $search,
                'role' => $role,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Formulier voor nieuwe medewerker (US-03).
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

    /**
     * Formulier om een teamlid te bewerken (US-05).
     */
    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('team.edit', ['member' => $user]);
    }

    /**
     * Bijwerken van een teamlid met audit-trail (US-05 kern).
     * Alle logica staat in UserService::updateWithAudit — die garandeert
     * atomiciteit + self-demotion guard + AVG-audit-rij per gewijzigd veld.
     */
    public function update(UpdateTeamMemberRequest $request, User $user, UserService $users): RedirectResponse
    {
        $users->updateWithAudit(
            member: $user,
            payload: $request->validatedPayload(),
            changedBy: $request->user(),
        );

        return redirect()
            ->route('team.index')
            ->with('success', 'Medewerker bijgewerkt.');
    }

    /**
     * Deactiveert een teamlid (US-06).
     *
     * 'delete' in UserPolicy = deactiveren (geen hard delete vanwege Wgbo
     * 20-jaar dossierplicht). Self-deactivation is geblokkeerd in de policy.
     */
    public function deactivate(User $user, UserService $users): RedirectResponse
    {
        $this->authorize('delete', $user);

        $users->deactivate($user, auth()->user());

        return redirect()
            ->route('team.index')
            ->with('success', "{$user->name} is gedeactiveerd.");
    }

    /**
     * Heractiveert een gedeactiveerd teamlid (US-06).
     * Wachtwoord blijft behouden — user kan direct weer inloggen.
     */
    public function activate(User $user, UserService $users): RedirectResponse
    {
        $this->authorize('restore', $user);

        $users->activate($user, auth()->user());

        return redirect()
            ->route('team.index')
            ->with('success', "{$user->name} is heractiveerd.");
    }
}

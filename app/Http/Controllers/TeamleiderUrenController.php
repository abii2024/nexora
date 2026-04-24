<?php

namespace App\Http\Controllers;

use App\Enums\UrenStatus;
use App\Http\Requests\Uren\AfkeurUrenRequest;
use App\Models\Urenregistratie;
use App\Services\UrenregistratieService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * US-13: teamleider-kant van uren-beoordeling.
 * Routes achter `teamleider`-middleware, rol + team scope via policy.
 */
class TeamleiderUrenController extends Controller
{
    public function __construct(protected UrenregistratieService $uren)
    {
    }

    /**
     * AC-1: overzicht met alle ingediende uren gegroepeerd per medewerker.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Urenregistratie::class);

        $rows = $this->uren
            ->scopedForTeamleider($request->user(), UrenStatus::Ingediend)
            ->get();

        $byUser = $rows->groupBy('user_id');

        return view('teamleider.uren.index', [
            'groups' => $byUser,
            'totalRows' => $rows->count(),
        ]);
    }

    /**
     * AC-2: goedkeuren — direct, geen modal.
     */
    public function approve(Request $request, Urenregistratie $uren): RedirectResponse
    {
        $this->authorize('goedkeuren', $uren);

        $this->uren->approve($uren, $request->user());

        return redirect()
            ->route('teamleider.uren.index')
            ->with('success', 'Uren goedgekeurd.');
    }

    /**
     * AC-3: afkeuren via form met verplichte teamleider_notitie (min 10 tekens).
     */
    public function reject(AfkeurUrenRequest $request, Urenregistratie $uren): RedirectResponse
    {
        $reden = $request->validatedPayload();

        $this->uren->reject($uren, $request->user(), $reden);

        return redirect()
            ->route('teamleider.uren.index')
            ->with('success', 'Uren afgekeurd — reden verzonden naar medewerker.');
    }
}

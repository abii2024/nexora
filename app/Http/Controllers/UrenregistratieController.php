<?php

namespace App\Http\Controllers;

use App\Enums\UrenStatus;
use App\Http\Requests\Uren\StoreUrenregistratieRequest;
use App\Http\Requests\Uren\UpdateUrenregistratieRequest;
use App\Models\Client;
use App\Models\Urenregistratie;
use App\Services\UrenregistratieService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UrenregistratieController extends Controller
{
    public function __construct(protected UrenregistratieService $uren)
    {
    }

    /**
     * Uren-overzicht met 4 status-tabs (US-11 AC-1).
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Urenregistratie::class);

        $user = $request->user();
        $statusParam = $request->query('status', 'concept');
        $status = UrenStatus::tryFrom((string) $statusParam) ?? UrenStatus::Concept;

        $rows = $this->uren->getPaginated($user, $status);

        $counts = [
            UrenStatus::Concept->value => $this->uren->scopedForUser($user, UrenStatus::Concept)->count(),
            UrenStatus::Ingediend->value => $this->uren->scopedForUser($user, UrenStatus::Ingediend)->count(),
            UrenStatus::Goedgekeurd->value => $this->uren->scopedForUser($user, UrenStatus::Goedgekeurd)->count(),
            UrenStatus::Afgekeurd->value => $this->uren->scopedForUser($user, UrenStatus::Afgekeurd)->count(),
        ];

        return view('uren.index', [
            'rows' => $rows,
            'activeStatus' => $status,
            'counts' => $counts,
        ]);
    }

    /**
     * Formulier nieuwe uren-entry (US-11 AC-2).
     */
    public function create(): View
    {
        $this->authorize('create', Urenregistratie::class);

        return view('uren.create', [
            'clients' => $this->ownCaregiverClients(),
        ]);
    }

    /**
     * Opslaan nieuwe uren-entry (US-11 AC-3 + AC-4).
     */
    public function store(StoreUrenregistratieRequest $request): RedirectResponse
    {
        $user = $request->user();
        $payload = $request->validatedPayload();

        // Extra defense in depth: cliënt moet aan deze zorgbegeleider gekoppeld zijn.
        if (!$this->ownCaregiverClients()->contains('id', $payload['client_id'])) {
            abort(403, 'Deze cliënt hoort niet bij jouw caseload.');
        }

        $uren = $this->uren->create($user, $payload);

        return redirect()
            ->route('uren.index', ['status' => UrenStatus::Concept->value])
            ->with('success', 'Uren geregistreerd als concept ('.number_format($uren->uren, 2, ',', '').' u).');
    }

    /**
     * Bewerkformulier — alleen concept/afgekeurd (US-11 AC-5).
     */
    public function edit(Urenregistratie $uren): View
    {
        $this->authorize('update', $uren);

        return view('uren.edit', [
            'uren' => $uren,
            'clients' => $this->ownCaregiverClients(),
        ]);
    }

    public function update(UpdateUrenregistratieRequest $request, Urenregistratie $uren): RedirectResponse
    {
        $payload = $request->validatedPayload();

        if (!$this->ownCaregiverClients()->contains('id', $payload['client_id'])) {
            abort(403, 'Deze cliënt hoort niet bij jouw caseload.');
        }

        $this->uren->update($uren, $payload);

        return redirect()
            ->route('uren.index', ['status' => $uren->status->value])
            ->with('success', 'Uren bijgewerkt.');
    }

    /**
     * Concept → Ingediend (US-12 AC-1 + AC-2).
     */
    public function submit(Request $request, Urenregistratie $uren): RedirectResponse
    {
        $this->authorize('submit', $uren);

        $this->uren->submit($uren, $request->user());

        return redirect()
            ->route('uren.index', ['status' => UrenStatus::Ingediend->value])
            ->with('success', 'Uren ingediend voor goedkeuring.');
    }

    /**
     * Ingediend → Concept (US-12 AC-3).
     */
    public function withdraw(Request $request, Urenregistratie $uren): RedirectResponse
    {
        $this->authorize('withdraw', $uren);

        $this->uren->withdraw($uren, $request->user());

        return redirect()
            ->route('uren.index', ['status' => UrenStatus::Concept->value])
            ->with('success', 'Uren teruggetrokken — nu weer bewerkbaar als concept.');
    }

    /**
     * Afgekeurd → Ingediend (US-12 AC-4) — combineert update + submit.
     */
    public function resubmit(UpdateUrenregistratieRequest $request, Urenregistratie $uren): RedirectResponse
    {
        $this->authorize('resubmit', $uren);

        $payload = $request->validatedPayload();

        if (!$this->ownCaregiverClients()->contains('id', $payload['client_id'])) {
            abort(403, 'Deze cliënt hoort niet bij jouw caseload.');
        }

        $this->uren->update($uren, $payload);
        $this->uren->resubmit($uren, $request->user());

        return redirect()
            ->route('uren.index', ['status' => UrenStatus::Ingediend->value])
            ->with('success', 'Uren gecorrigeerd en opnieuw ingediend.');
    }

    /**
     * De cliënten waarvoor de huidige zorgbegeleider uren mag registreren
     * — eigen primair/secundair/tertiair-koppelingen, alleen actieve cliënten.
     *
     * @return \Illuminate\Support\Collection<int, Client>
     */
    protected function ownCaregiverClients(): \Illuminate\Support\Collection
    {
        $user = auth()->user();

        return Client::query()
            ->whereHas('caregivers', fn ($q) => $q->where('users.id', $user->id))
            ->orderBy('achternaam')
            ->orderBy('voornaam')
            ->get(['id', 'voornaam', 'achternaam']);
    }
}

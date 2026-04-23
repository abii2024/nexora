<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clients\CaregiverAssignmentRequest;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Models\Client;
use App\Models\User;
use App\Services\ClientService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(protected ClientService $clients)
    {
    }

    /**
     * Cliëntenoverzicht met rol-gebaseerde weergave, zoek en filter (US-09).
     *
     *  - Teamleider: tabel met alle team-cliënten + totaal-banner + create-CTA.
     *  - Zorgbegeleider: kaartweergave met alleen gekoppelde cliënten + info-banner.
     *  - Filters (search/status/care_type/sort) blijven bij paginatie via withQueryString.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Client::class);

        $user = auth()->user();

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => $request->query('status'),
            'care_type' => $request->query('care_type'),
            'sort' => $request->query('sort', 'name'),
        ];

        $clients = $this->clients->getPaginated($user, $filters);

        // Totalen voor de rol-banner (teamleider ziet actief/wacht/inactief,
        // zorgbegeleider ziet alleen eigen-caseload-tellers).
        $scope = $this->clients->scopedForUser($user);
        $totals = [
            'totaal' => (clone $scope)->count(),
            'actief' => (clone $scope)->where('status', Client::STATUS_ACTIEF)->count(),
            'wacht' => (clone $scope)->where('status', Client::STATUS_WACHT)->count(),
            'inactief' => (clone $scope)->where('status', Client::STATUS_INACTIEF)->count(),
        ];

        return view('clients.index', [
            'clients' => $clients,
            'filters' => $filters,
            'totals' => $totals,
        ]);
    }

    /**
     * Formulier voor nieuwe cliënt (US-07 AC-1 + US-08 Begeleiders-sectie).
     */
    public function create(): View
    {
        $this->authorize('create', Client::class);

        return view('clients.create', [
            'availableCaregivers' => $this->availableCaregivers(),
        ]);
    }

    /**
     * Opslaan van nieuwe cliënt (US-07 AC-5 + US-08 syncCaregivers).
     */
    public function store(StoreClientRequest $request, CaregiverAssignmentRequest $caregiverRequest): RedirectResponse
    {
        $client = $this->clients->create($request->validatedPayload());

        $payload = $caregiverRequest->validatedPayload();
        $this->clients->syncCaregivers(
            client: $client,
            userIds: $payload['userIds'],
            primaryId: $payload['primaryId'],
            changedBy: $request->user(),
        );

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Cliënt aangemaakt.');
    }

    /**
     * Cliënt-details — US-08 toont gekoppelde begeleiders.
     */
    public function show(Client $client): View
    {
        $this->authorize('view', $client);

        $client->load(['caregivers', 'createdBy', 'team']);

        return view('clients.show', ['client' => $client]);
    }

    /**
     * Beheer caregiver-koppelingen (US-08 dedicated edit-pagina).
     */
    public function editCaregivers(Client $client): View
    {
        $this->authorize('update', $client);

        $client->load('caregivers');

        return view('clients.caregivers', [
            'client' => $client,
            'availableCaregivers' => $this->availableCaregivers(),
        ]);
    }

    /**
     * Synchroniseer caregiver-koppelingen (US-08 kern).
     */
    public function updateCaregivers(CaregiverAssignmentRequest $request, Client $client): RedirectResponse
    {
        $payload = $request->validatedPayload();

        $this->clients->syncCaregivers(
            client: $client,
            userIds: $payload['userIds'],
            primaryId: $payload['primaryId'],
            changedBy: $request->user(),
        );

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Begeleiders bijgewerkt.');
    }

    /**
     * Alle actieve zorgbegeleiders uit het eigen team — selecteerbaar in forms.
     */
    protected function availableCaregivers(): \Illuminate\Support\Collection
    {
        return User::query()
            ->where('team_id', auth()->user()->team_id)
            ->where('role', User::ROLE_ZORGBEGELEIDER)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'dienstverband']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clients\StoreClientRequest;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ClientController extends Controller
{
    public function __construct(protected ClientService $clients) {}

    /**
     * Cliëntenoverzicht (US-07 stub — volledige uitwerking met zoek+filter
     * komt in US-09).
     */
    public function index(): View
    {
        $this->authorize('viewAny', Client::class);

        $members = $this->clients->scopedForUser(auth()->user())
            ->orderBy('achternaam')
            ->orderBy('voornaam')
            ->get();

        return view('clients.index', ['clients' => $members]);
    }

    /**
     * Formulier voor nieuwe cliënt (US-07 AC-1).
     */
    public function create(): View
    {
        $this->authorize('create', Client::class);

        return view('clients.create');
    }

    /**
     * Opslaan van nieuwe cliënt (US-07 AC-5).
     * Validatie + whitelist via StoreClientRequest; persist via service.
     */
    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = $this->clients->create($request->validatedPayload());

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Cliënt aangemaakt.');
    }

    /**
     * Cliënt-details (US-07 minimale show; uitgebreid in US-09/US-10).
     */
    public function show(Client $client): View
    {
        $this->authorize('view', $client);

        return view('clients.show', ['client' => $client]);
    }
}

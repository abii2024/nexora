<?php

namespace App\Exceptions;

use App\Enums\UrenStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

/**
 * US-12 AC-5: wordt opgegooid vanuit UrenregistratieService::transition()
 * wanneer een niet-toegestane state-transitie wordt geprobeerd.
 *
 * Rendert standaard als 422 zodat de UI een nette NL fout kan tonen.
 */
class InvalidStateTransitionException extends RuntimeException
{
    public function __construct(
        public readonly UrenStatus $from,
        public readonly UrenStatus $to,
        ?string $message = null,
    ) {
        parent::__construct(
            $message ?? "Transitie van '{$from->label()}' naar '{$to->label()}' is niet toegestaan."
        );
    }

    public function render(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 422);
        }

        return back()
            ->withErrors(['state' => $this->getMessage()])
            ->setStatusCode(422);
    }
}

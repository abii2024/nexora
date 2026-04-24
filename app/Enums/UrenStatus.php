<?php

namespace App\Enums;

/**
 * State-machine voor urenregistratie (US-11 + US-12 + US-13).
 *
 *  - Concept      — door zorgbegeleider gemaakt/bewerkt, nog niet ingediend
 *  - Ingediend    — aangeboden ter goedkeuring (read-only voor zorgbeg)
 *  - Goedgekeurd  — door teamleider goedgekeurd (immutable)
 *  - Afgekeurd    — door teamleider afgekeurd (weer bewerkbaar door zorgbeg)
 */
enum UrenStatus: string
{
    case Concept = 'concept';
    case Ingediend = 'ingediend';
    case Goedgekeurd = 'goedgekeurd';
    case Afgekeurd = 'afgekeurd';

    public function label(): string
    {
        return match ($this) {
            self::Concept => 'Concept',
            self::Ingediend => 'Ingediend',
            self::Goedgekeurd => 'Goedgekeurd',
            self::Afgekeurd => 'Afgekeurd',
        };
    }

    public function badgeTone(): string
    {
        return match ($this) {
            self::Concept => 'neutral',
            self::Ingediend => 'warning',
            self::Goedgekeurd => 'success',
            self::Afgekeurd => 'danger',
        };
    }

    /**
     * US-11 AC-5: bewerken mag alleen bij concept/afgekeurd.
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::Concept, self::Afgekeurd], true);
    }
}

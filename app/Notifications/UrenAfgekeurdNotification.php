<?php

namespace App\Notifications;

use App\Models\Urenregistratie;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Database-notification naar de zorgbegeleider zodra een teamleider
 * zijn ingediende uren heeft afgekeurd met reden (US-13 AC-2 + AC-4).
 *
 * De `afkeur_reden` zit in de payload én in de Urenregistratie zelf
 * (kolom `afkeur_reden`), zodat de edit-form hem ook kan tonen.
 */
class UrenAfgekeurdNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Urenregistratie $uren,
        protected User $afgekeurdDoor,
        protected string $reden,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'uren_afgekeurd',
            'uren_id' => $this->uren->id,
            'datum' => $this->uren->datum?->format('Y-m-d'),
            'uren' => (string) $this->uren->uren,
            'client_id' => $this->uren->client_id,
            'client_name' => $this->uren->client?->fullName(),
            'afgekeurd_door_user_id' => $this->afgekeurdDoor->id,
            'afgekeurd_door_name' => $this->afgekeurdDoor->name,
            'afkeur_reden' => $this->reden,
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\Urenregistratie;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Database-notification naar de zorgbegeleider zodra een teamleider
 * zijn ingediende uren heeft goedgekeurd (US-13 AC-2).
 *
 * Database-only — email is expliciet out-of-scope.
 */
class UrenGoedgekeurdNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Urenregistratie $uren,
        protected User $goedgekeurdDoor,
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
            'type' => 'uren_goedgekeurd',
            'uren_id' => $this->uren->id,
            'datum' => $this->uren->datum?->format('Y-m-d'),
            'uren' => (string) $this->uren->uren,
            'client_id' => $this->uren->client_id,
            'client_name' => $this->uren->client?->fullName(),
            'goedgekeurd_door_user_id' => $this->goedgekeurdDoor->id,
            'goedgekeurd_door_name' => $this->goedgekeurdDoor->name,
        ];
    }
}
